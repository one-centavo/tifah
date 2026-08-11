<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Bill;
use App\Models\BillDetail;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Lot;
use App\Models\Medicine;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BillService
{
    /**
     * Allocate lots for a medicine following the FEFO (First Expired, First Out) rule,
     * while respecting manually locked lot selections.
     *
     * @param  array<int, int>  $lockedAssignments  [lot_id => quantity]
     * @return array{
     *     allocations: array<int, array<string, mixed>>,
     *     fulfilled_quantity: int,
     *     shortfall_quantity: int
     * }
     */
    public function allocateFefoLots(Medicine $medicine, int $requestedQuantity, array $lockedAssignments = []): array
    {
        $activeLots = $medicine->lots()
            ->where('status', 'active')
            ->where('current_quantity', '>', 0)
            ->whereDate('expiration_date', '>=', now()->toDateString())
            ->orderBy('expiration_date', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        $allocations = [];
        $totalAllocated = 0;
        $lockedLotIds = array_keys($lockedAssignments);

        // First process locked lots
        foreach ($activeLots as $index => $lot) {
            $isLocked = in_array($lot->id, $lockedLotIds, true);
            if ($isLocked) {
                $manualQty = min((int) $lockedAssignments[$lot->id], $lot->current_quantity);
                if ($manualQty > 0) {
                    $allocations[$lot->id] = [
                        'lot_id' => $lot->id,
                        'batch_number' => $lot->batch_number,
                        'expiration_date' => $lot->expiration_date,
                        'current_quantity' => $lot->current_quantity,
                        'allocated_quantity' => $manualQty,
                        'unit_price' => (float) ($medicine->selling_price ?? 0),
                        'subtotal' => round($manualQty * (float) ($medicine->selling_price ?? 0), 0),
                        'is_locked' => true,
                        'is_fefo_priority' => ($index === 0),
                    ];
                    $totalAllocated += $manualQty;
                }
            }
        }

        // Now allocate the remaining requested quantity to unlocked lots by FEFO
        $remainingNeeded = max(0, $requestedQuantity - $totalAllocated);

        foreach ($activeLots as $index => $lot) {
            if (isset($allocations[$lot->id])) {
                continue;
            }

            if ($remainingNeeded <= 0) {
                break;
            }

            $toAssign = min($remainingNeeded, $lot->current_quantity);
            if ($toAssign > 0) {
                $allocations[$lot->id] = [
                    'lot_id' => $lot->id,
                    'batch_number' => $lot->batch_number,
                    'expiration_date' => $lot->expiration_date,
                    'current_quantity' => $lot->current_quantity,
                    'allocated_quantity' => $toAssign,
                    'unit_price' => (float) ($medicine->selling_price ?? 0),
                    'subtotal' => round($toAssign * (float) ($medicine->selling_price ?? 0), 0),
                    'is_locked' => false,
                    'is_fefo_priority' => ($index === 0),
                ];

                $totalAllocated += $toAssign;
                $remainingNeeded -= $toAssign;
            }
        }

        return [
            'allocations' => array_values($allocations),
            'fulfilled_quantity' => $totalAllocated,
            'shortfall_quantity' => max(0, $requestedQuantity - $totalAllocated),
        ];
    }

    /**
     * Validate whether a customer is eligible for a credit sale against their credit limit.
     *
     * @throws ValidationException
     */
    public function validateCreditEligibility(Customer $customer, float $saleAmount): void
    {
        $unpaidCreditSum = (float) Bill::query()
            ->where('id_customer', $customer->id)
            ->where('status', 'active')
            ->where('payment_method', 'credit')
            ->sum('total_amount');

        $projectedDebt = $unpaidCreditSum + $saleAmount;
        $creditLimit = (float) $customer->credit_limit;

        if ($projectedDebt > $creditLimit) {
            throw ValidationException::withMessages([
                'payment_method' => 'El cliente ha superado su límite de crédito autorizado.',
            ]);
        }
    }

    /**
     * Generate the next unique sequential invoice number.
     */
    public function generateInvoiceNumber(): string
    {
        $lastBill = Bill::query()->latest('id')->first();
        $nextNumber = ($lastBill?->id ?? 0) + 1;

        return 'FAC-' . str_pad((string) $nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Create a sale and discharge inventory atomically with pessimistic concurrency locking.
     *
     * @param  array{
     *     id_customer: int,
     *     payment_method: string,
     *     payment_due_date?: ?string,
     * }  $billData
     * @param  array<int, array{
     *     lot_id: int,
     *     quantity: int,
     *     unit_price: float,
     * }>  $items
     *
     * @throws ValidationException
     */
    public function createSale(array $billData, array $items, int $userId): Bill
    {
        if (empty($items)) {
            throw ValidationException::withMessages([
                'items' => 'Debe agregar al menos un medicamento a la venta.',
            ]);
        }

        $customer = Customer::findOrFail($billData['id_customer']);

        return DB::transaction(function () use ($billData, $items, $userId, $customer) {
            $lotIds = array_column($items, 'lot_id');

            // Lock referenced lots against concurrent updates
            $lockedLots = Lot::whereIn('id', $lotIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Validate that all lots exist and have sufficient stock
            $totalAmount = 0.0;
            foreach ($items as $item) {
                $lotId = (int) $item['lot_id'];
                $qty = (int) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];

                if (! isset($lockedLots[$lotId])) {
                    throw ValidationException::withMessages([
                        'items' => 'Uno de los lotes seleccionados no existe en el sistema.',
                    ]);
                }

                $lot = $lockedLots[$lotId];

                if ($lot->current_quantity < $qty) {
                    throw ValidationException::withMessages([
                        'items' => "Las existencias del lote {$lot->batch_number} cambiaron durante la operación o no son suficientes. Por favor revise el inventario.",
                    ]);
                }

                $lineSubtotal = round($qty * $unitPrice, 0);
                $totalAmount += $lineSubtotal;
            }

            $roundedTotal = round($totalAmount, 0);

            // Validate credit terms if payment method is credit
            if (($billData['payment_method'] ?? 'cash') === 'credit') {
                if (empty($billData['payment_due_date'])) {
                    throw ValidationException::withMessages([
                        'payment_due_date' => 'La fecha de vencimiento de pago es obligatoria para ventas a crédito.',
                    ]);
                }

                $this->validateCreditEligibility($customer, $roundedTotal);
            }

            $invoiceNumber = $this->generateInvoiceNumber();

            // Create Bill
            $bill = Bill::create([
                'id_customer' => $customer->id,
                'invoice_number' => $invoiceNumber,
                'status' => 'active',
                'payment_method' => $billData['payment_method'] ?? 'cash',
                'payment_due_date' => ($billData['payment_method'] ?? 'cash') === 'credit' ? $billData['payment_due_date'] : null,
                'total_amount' => $roundedTotal,
                'created_by' => $userId,
            ]);

            // Create BillDetails and discharge stock
            foreach ($items as $item) {
                $lotId = (int) $item['lot_id'];
                $qty = (int) $item['quantity'];
                $unitPrice = (float) $item['unit_price'];
                $lineSubtotal = round($qty * $unitPrice, 0);

                $lot = $lockedLots[$lotId];

                BillDetail::create([
                    'bill_id' => $bill->id,
                    'lot_id' => $lot->id,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $lineSubtotal,
                ]);

                $previousBalance = $lot->current_quantity;
                $newBalance = $previousBalance - $qty;

                $lot->update([
                    'current_quantity' => $newBalance,
                    'updated_by' => $userId,
                ]);

                InventoryMovement::create([
                    'lot_id' => $lot->id,
                    'type' => 'exit',
                    'quantity' => $qty,
                    'previous_balance' => $previousBalance,
                    'new_balance' => $newBalance,
                    'concept' => 'Venta / Factura #' . $bill->invoice_number,
                    'reference_id' => $bill->id,
                    'created_by' => $userId,
                ]);
            }

            return $bill->load(['details.lot.medicine', 'customer']);
        });
    }

    /**
     * Annul an active bill and return items to their source lots.
     *
     * @throws ValidationException
     */
    public function annulBill(Bill $bill, string $reason, int $userId): Bill
    {
        if ($bill->status !== 'active') {
            throw ValidationException::withMessages([
                'bill' => 'Solo se pueden anular facturas que se encuentren en estado activo.',
            ]);
        }

        return DB::transaction(function () use ($bill, $reason, $userId) {
            foreach ($bill->details as $detail) {
                /** @var Lot $lot */
                $lot = Lot::where('id', $detail->lot_id)->lockForUpdate()->first();

                if ($lot) {
                    $previousBalance = $lot->current_quantity;
                    $newBalance = $previousBalance + $detail->quantity;

                    $lot->update([
                        'current_quantity' => $newBalance,
                        'updated_by' => $userId,
                    ]);

                    InventoryMovement::create([
                        'lot_id' => $lot->id,
                        'type' => 'entry',
                        'quantity' => $detail->quantity,
                        'previous_balance' => $previousBalance,
                        'new_balance' => $newBalance,
                        'concept' => 'Anulación Factura #' . $bill->invoice_number,
                        'adjustment_reason' => $reason,
                        'reference_id' => $bill->id,
                        'created_by' => $userId,
                    ]);
                }
            }

            $bill->update([
                'status' => 'annulled',
                'annulled_reason' => $reason,
                'annulled_by' => $userId,
                'annulled_at' => now(),
                'updated_by' => $userId,
            ]);

            return $bill->fresh(['details.lot.medicine', 'customer', 'annuller']);
        });
    }
}

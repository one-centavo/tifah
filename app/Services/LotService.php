<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Lot;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\DB;

class LotService
{
    /**
     * Receive merchandise and save lots inside a database transaction.
     *
     * @param  array<int, array<string, mixed>>  $lotsData
     */
    public function receiveMerchandise(array $lotsData, int $supplierId, string $receptionDate): void
    {
        DB::transaction(function () use ($lotsData, $supplierId, $receptionDate) {
            $totalCost = 0.0;
            foreach ($lotsData as $lot) {
                $totalCost += (int) $lot['quantity'] * (float) $lot['unit_purchase_price'];
            }

            $purchaseOrder = PurchaseOrder::create([
                'supplier_id' => $supplierId,
                'status' => 'received',
                'expected_date' => $receptionDate,
                'received_at' => $receptionDate,
                'total_estimated' => $totalCost,
                'created_by' => auth()->id(),
            ]);

            foreach ($lotsData as $lot) {
                $newLot = Lot::create([
                    'medicine_id' => $lot['medicine_id'],
                    'purchase_order_id' => $purchaseOrder->id,
                    'batch_number' => $lot['batch_number'],
                    'expiration_date' => $lot['expiration_date'],
                    'initial_quantity' => $lot['quantity'],
                    'current_quantity' => $lot['quantity'],
                    'reception_date' => $receptionDate,
                    'unit_purchase_price' => $lot['unit_purchase_price'],
                    'status' => $lot['status'] ?? 'active',
                    'created_by' => auth()->id(),
                ]);

                InventoryMovement::create([
                    'lot_id' => $newLot->id,
                    'type' => 'entry',
                    'quantity' => $newLot->initial_quantity,
                    'previous_balance' => 0,
                    'new_balance' => $newLot->initial_quantity,
                    'concept' => 'Merchandise reception - Batch '.$newLot->batch_number,
                    'reference_id' => $purchaseOrder->id,
                    'created_by' => auth()->id(),
                ]);
            }
        });
    }

    /**
     * Soft delete an existing lot and assign the deleter.
     */
    public function delete(Lot $lot): void
    {
        DB::transaction(function () use ($lot) {
            $userId = auth()->id();

            // Create inventory movement adjustment to zero out the stock on deletion
            InventoryMovement::create([
                'lot_id' => $lot->id,
                'type' => 'adjustment',
                'quantity' => -$lot->current_quantity,
                'previous_balance' => $lot->current_quantity,
                'new_balance' => 0,
                'concept' => 'Lot deleted/archived - Batch '.$lot->batch_number,
                'reference_id' => $lot->purchase_order_id,
                'created_by' => $userId,
            ]);

            $lot->update([
                'current_quantity' => 0,
                'deleted_by' => $userId,
            ]);

            $lot->delete();
        });
    }

    /**
     * Adjust the quantity of a specific movement and record the compensating movement.
     */
    public function adjustMovement(InventoryMovement $movement, int $newQuantity, string $reason, string $observations, int $userId): void
    {
        DB::transaction(function () use ($movement, $newQuantity, $reason, $observations, $userId) {
            $lot = $movement->lot;
            $quantityDiff = $newQuantity - $movement->quantity;

            InventoryMovement::create([
                'lot_id' => $lot->id,
                'type' => 'adjustment',
                'quantity' => $quantityDiff,
                'previous_balance' => $lot->current_quantity,
                'new_balance' => $lot->current_quantity + $quantityDiff,
                'concept' => "Ajuste de cantidad del movimiento #{$movement->id}",
                'adjustment_reason' => $reason,
                'observations' => $observations,
                'reference_id' => $movement->id,
                'created_by' => $userId,
            ]);

            $lot->update([
                'current_quantity' => $lot->current_quantity + $quantityDiff,
                'updated_by' => $userId,
            ]);
        });
    }
}

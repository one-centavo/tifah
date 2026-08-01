<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Medicine;
use App\Models\MedicineBarcode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MedicineService
{
    /**
     * Search for a duplicate active medicine with matching key attributes.
     *
     * @param  array<string, mixed>  $data
     */
    public function findDuplicate(array $data): ?Medicine
    {
        $query = Medicine::query()
            ->where('name', $data['name'])
            ->where('concentration_value', $data['concentration_value'])
            ->where('concentration_unit_id', $data['concentration_unit_id'])
            ->where('container_id', $data['container_id'])
            ->where('content_quantity', $data['content_quantity'])
            ->where('content_unit_id', $data['content_unit_id'])
            ->where('laboratory_id', $data['laboratory_id']);

        if (empty($data['generic_name'])) {
            $query->whereNull('generic_name');
        } else {
            $query->where('generic_name', $data['generic_name']);
        }

        return $query->first();
    }

    /**
     * Create a new medicine and its main barcode in a database transaction.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Medicine
    {
        $barcode = $data['barcode'] ?? null;
        unset($data['barcode']);

        if (empty($barcode)) {
            do {
                $barcode = '999'.str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
            } while (MedicineBarcode::where('barcode', $barcode)->exists());
        }

        if (MedicineBarcode::where('barcode', $barcode)->exists()) {
            throw ValidationException::withMessages([
                'barcode' => 'Este código de barras ya se encuentra registrado.',
            ]);
        }

        return DB::transaction(function () use ($data, $barcode) {
            $data['created_by'] = auth()->id();
            $medicine = Medicine::create($data);

            $medicine->barcodes()->create([
                'barcode' => $barcode,
                'is_main' => true,
                'created_by' => auth()->id(),
            ]);

            return $medicine;
        });
    }

    /**
     * Link a new barcode to an existing medicine.
     */
    public function linkBarcode(Medicine $medicine, string $barcode): MedicineBarcode
    {
        if (MedicineBarcode::where('barcode', $barcode)->exists()) {
            throw ValidationException::withMessages([
                'barcode' => 'Este código de barras ya se encuentra registrado.',
            ]);
        }

        return $medicine->barcodes()->create([
            'barcode' => $barcode,
            'is_main' => false,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Update an existing medicine and manage its barcodes in a transaction.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, array{id: ?int, barcode: string, is_new: bool, is_main: bool}>  $barcodes
     */
    public function update(Medicine $medicine, array $data, array $barcodes): Medicine
    {
        $hasLotsOrSales = $medicine->hasLotsOrSales();

        if ($hasLotsOrSales) {
            // Protect Master Data fields from being modified when there are lots or sales.
            $allowedFields = ['selling_price', 'min_stock', 'description', 'category_id', 'is_cold_chain', 'is_special_control'];
            $data = array_intersect_key($data, array_flip($allowedFields));
        }

        // Validate that if master data is updated, the new combination is unique.
        if (! $hasLotsOrSales) {
            $checkData = array_merge($medicine->toArray(), $data);
            $duplicate = $this->findDuplicate($checkData);
            if ($duplicate && $duplicate->id !== $medicine->id) {
                throw ValidationException::withMessages([
                    'name' => 'Ya existe un medicamento registrado con esta misma combinación de Nombre Comercial, Genérico, Concentración, Presentación y Laboratorio.',
                ]);
            }
        }

        // Validate barcode uniqueness and format
        foreach ($barcodes as $b) {
            $barcodeVal = $b['barcode'] ?? '';
            if (empty($barcodeVal)) {
                throw ValidationException::withMessages([
                    'barcodes' => 'El código de barras no puede estar vacío.',
                ]);
            }

            // Format Constraint: numeric only, between 8 and 14 characters
            if (! preg_match('/^[0-9]+$/', $barcodeVal)) {
                throw ValidationException::withMessages([
                    'barcodes' => 'El código de barras debe estar compuesto únicamente por números.',
                ]);
            }
            $len = strlen($barcodeVal);
            if ($len < 8 || $len > 14) {
                throw ValidationException::withMessages([
                    'barcodes' => 'El código de barras debe tener entre 8 y 14 dígitos.',
                ]);
            }

            $query = MedicineBarcode::where('barcode', $barcodeVal);
            if (! empty($b['id'])) {
                $query->where('id', '!=', $b['id']);
            }
            if ($query->exists()) {
                throw ValidationException::withMessages([
                    'barcodes' => "El código de barras {$barcodeVal} ya se encuentra registrado.",
                ]);
            }
        }

        return DB::transaction(function () use ($medicine, $data, $barcodes, $hasLotsOrSales) {
            $userId = auth()->id();
            $data['updated_by'] = $userId;
            $medicine->update($data);

            $stagedBarcodeIds = [];

            foreach ($barcodes as $b) {
                $barcodeVal = $b['barcode'] ?? '';
                $isNew = $b['is_new'] ?? false;
                $barcodeId = $b['id'] ?? null;

                if ($isNew) {
                    $newBarcode = $medicine->barcodes()->create([
                        'barcode' => $barcodeVal,
                        'is_main' => $b['is_main'] ?? false,
                        'created_by' => $userId,
                    ]);
                    $stagedBarcodeIds[] = $newBarcode->id;
                } else {
                    $existingBarcode = MedicineBarcode::find($barcodeId);
                    if ($existingBarcode) {
                        if (! $hasLotsOrSales) {
                            $existingBarcode->update([
                                'barcode' => $barcodeVal,
                                'updated_by' => $userId,
                            ]);
                        }
                        $stagedBarcodeIds[] = $existingBarcode->id;
                    }
                }
            }

            // Sync deletion
            $currentBarcodeIds = $medicine->barcodes()->pluck('id')->toArray();
            $toDeleteIds = array_diff($currentBarcodeIds, $stagedBarcodeIds);

            if (! empty($toDeleteIds)) {
                if ($hasLotsOrSales) {
                    throw ValidationException::withMessages([
                        'barcodes' => 'No se pueden eliminar códigos de barras de un medicamento con movimientos de inventario.',
                    ]);
                }

                foreach ($toDeleteIds as $delId) {
                    $barToDel = MedicineBarcode::find($delId);
                    if ($barToDel) {
                        $barToDel->update(['deleted_by' => $userId]);
                        $barToDel->delete();
                    }
                }
            }

            return $medicine->fresh();
        });
    }

    /**
     * Soft delete a medicine and all associated barcodes in a transaction.
     */
    public function delete(Medicine $medicine): void
    {
        DB::transaction(function () use ($medicine) {
            $userId = auth()->id();

            $medicine->update(['deleted_by' => $userId]);
            $medicine->delete();

            foreach ($medicine->barcodes as $barcode) {
                $barcode->update(['deleted_by' => $userId]);
                $barcode->delete();
            }
        });
    }
}

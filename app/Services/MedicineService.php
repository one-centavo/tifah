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

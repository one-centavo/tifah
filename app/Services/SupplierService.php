<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Lot;
use App\Models\Supplier;
use Illuminate\Validation\ValidationException;

class SupplierService
{
    /**
     * Create a new supplier and record who created it.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Supplier
    {
        $data['created_by'] = auth()->id();

        return Supplier::create($data);
    }

    /**
     * Update an existing supplier and record who modified it.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Supplier $supplier, array $data): Supplier
    {
        $data['updated_by'] = auth()->id();
        $supplier->update($data);

        return $supplier;
    }

    /**
     * Soft delete an existing supplier if there are no active lots in stock.
     *
     * @throws ValidationException
     */
    public function delete(Supplier $supplier): void
    {
        $hasActiveLots = Lot::whereHas('purchaseOrder', function ($query) use ($supplier) {
            $query->where('supplier_id', $supplier->id);
        })->where('current_quantity', '>', 0)->exists();

        if ($hasActiveLots) {
            throw ValidationException::withMessages([
                'supplier' => 'No se puede eliminar el proveedor porque tiene lotes de mercancía activos en el inventario.',
            ]);
        }

        $supplier->update([
            'deleted_by' => auth()->id(),
        ]);

        $supplier->delete();
    }
}

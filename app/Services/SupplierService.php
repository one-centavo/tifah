<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Supplier;

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
}

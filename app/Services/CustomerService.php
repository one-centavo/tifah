<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Customer;
use Illuminate\Validation\ValidationException;

class CustomerService
{
    /**
     * Create a new customer and record who created it.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Customer
    {
        $data['created_by'] = auth()->id();

        return Customer::create($data);
    }

    /**
     * Update an existing customer and record who modified it.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Customer $customer, array $data): Customer
    {
        $data['updated_by'] = auth()->id();
        $customer->update($data);

        return $customer;
    }

    /**
     * Soft delete an existing customer if there are no associated bills in history.
     *
     * @throws ValidationException
     */
    public function delete(Customer $customer): void
    {
        $hasBills = $customer->bills()->exists();

        if ($hasBills) {
            throw ValidationException::withMessages([
                'customer' => 'No se puede eliminar el cliente porque tiene facturas asociadas en el histórico.',
            ]);
        }

        $customer->update([
            'deleted_by' => auth()->id(),
        ]);

        $customer->delete();
    }
}

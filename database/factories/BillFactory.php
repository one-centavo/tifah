<?php

namespace Database\Factories;

use App\Models\Bill;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Bill>
 */
class BillFactory extends Factory
{
    protected $model = Bill::class;

    public function definition(): array
    {
        return [
            'id_customer' => Customer::factory(),
            'status' => 'draft',
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }
}

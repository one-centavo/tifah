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
            'invoice_number' => 'FAC-' . str_pad((string) $this->faker->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'status' => 'active',
            'payment_method' => 'cash',
            'payment_due_date' => null,
            'total_amount' => $this->faker->randomFloat(2, 5000, 500000),
            'annulled_reason' => null,
            'annulled_by' => null,
            'annulled_at' => null,
            'created_by' => User::factory(),
            'updated_by' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    public function annulled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'annulled',
            'annulled_reason' => 'Anulación solicitada por error en digitación',
            'annulled_by' => User::factory(),
            'annulled_at' => now(),
        ]);
    }

    public function credit(): static
    {
        return $this->state(fn (array $attributes) => [
            'payment_method' => 'credit',
            'payment_due_date' => now()->addDays(30),
        ]);
    }
}


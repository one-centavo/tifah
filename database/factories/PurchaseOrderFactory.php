<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'status' => fake()->randomElement(['pending', 'received', 'cancelled']),
            'expected_date' => fake()->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
            'received_at' => null,
            'total_estimated' => fake()->randomFloat(2, 50000, 5000000),
            'created_by' => User::factory(),
            'updated_by' => null,
            'deleted_by' => null,
        ];
    }

    public function received(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'received',
            'received_at' => fake()->dateTimeBetween('-1 month', 'now'),
        ]);
    }
}

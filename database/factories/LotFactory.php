<?php

namespace Database\Factories;

use App\Models\Lot;
use App\Models\Medicine;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lot>
 */
class LotFactory extends Factory
{
    protected $model = Lot::class;

    public function definition(): array
    {
        $initialQuantity = $this->faker->numberBetween(10, 1000);

        return [
            'medicine_id' => Medicine::factory(),
            'purchase_order_id' => PurchaseOrder::factory(),
            'batch_number' => $this->faker->unique()->bothify('LOT-#####-??'),
            'expiration_date' => $this->faker->dateTimeBetween('+6 months', '+3 years')->format('Y-m-d'),
            'initial_quantity' => $initialQuantity,
            'current_quantity' => $initialQuantity,
            'reception_date' => $this->faker->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'unit_purchase_price' => $this->faker->randomFloat(2, 500, 50000),
            'status' => 'active',
            'created_by' => User::factory(),
            'updated_by' => null,
            'deleted_by' => null,
        ];
    }

    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'blocked',
        ]);
    }

    public function damaged(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'damaged',
            'current_quantity' => 0,
        ]);
    }
}

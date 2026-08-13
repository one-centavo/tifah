<?php

namespace Database\Factories;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Supplier>
 */
class SupplierFactory extends Factory
{
    protected $model = Supplier::class;

    private function calculateDv(string $nit): int
    {
        $weights = [3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71];
        $sum = 0;
        $reversedNit = strrev($nit);

        for ($i = 0; $i < strlen($reversedNit); $i++) {
            $sum += (int) $reversedNit[$i] * $weights[$i];
        }

        $remainder = $sum % 11;

        if ($remainder > 1) {
            return 11 - $remainder;
        }

        return $remainder;
    }

    public function definition(): array
    {
        return [
            'nit' => $this->faker->unique()->numerify('#########'),
            'dv' => fn (array $attributes) => $this->calculateDv($attributes['nit']),
            'name' => $this->faker->unique()->company(),
            'contact_person' => $this->faker->name(),
            'phone_number' => $this->faker->numerify('3#########'),
            'email' => $this->faker->unique()->safeEmail(),
            'address' => $this->faker->address(),
            'created_by' => User::factory(),
            'updated_by' => null,
            'deleted_by' => null,
        ];
    }
}

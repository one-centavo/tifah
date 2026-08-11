<?php

namespace Database\Factories;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Customer>
 */
class CustomerFactory extends Factory
{
    protected $model = Customer::class;

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
            'nit' => fake()->unique()->numerify('#########'),
            'dv' => fn (array $attributes) => $this->calculateDv($attributes['nit']),
            'name' => fake()->unique()->company(),
            'city' => fake()->city(),
            'address' => fake()->address(),
            'phone_number' => fake()->numerify('3#########'),
            'email' => fake()->unique()->safeEmail(),
            'is_active' => 1,
            'credit_limit' => fake()->randomElement([0, 1000000, 5000000, 10000000]),
            'created_by' => User::factory(),
            'updated_by' => null,
            'deleted_by' => null,
        ];
    }
}

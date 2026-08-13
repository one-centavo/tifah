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
            'nit' => $this->faker->unique()->numerify('#########'),
            'dv' => fn (array $attributes) => $this->calculateDv($attributes['nit']),
            'name' => $this->faker->unique()->company(),
            'city' => $this->faker->city(),
            'address' => $this->faker->address(),
            'phone_number' => $this->faker->numerify('3#########'),
            'email' => $this->faker->unique()->safeEmail(),
            'is_active' => 1,
            'credit_limit' => $this->faker->randomElement([0, 1000000, 5000000, 10000000]),
            'created_by' => User::factory(),
            'updated_by' => null,
            'deleted_by' => null,
        ];
    }
}

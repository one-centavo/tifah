<?php

namespace Database\Factories;

use App\Models\Laboratory;
use App\Models\SanitaryRegistry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SanitaryRegistry>
 */
class SanitaryRegistryFactory extends Factory
{
    protected $model = SanitaryRegistry::class;

    public function definition(): array
    {
        return [
            'registration_number' => 'INVIMA ' . fake()->unique()->numerify('20##M-#######'),
            'laboratory_id' => Laboratory::factory(),
            'expiration_date' => fake()->dateTimeBetween('+1 year', '+5 years')->format('Y-m-d'),
            'status' => 'valid',
            'description' => fake()->paragraph(),
            'created_by' => User::factory(),
            'updated_by' => null,
            'deleted_by' => null,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'expired',
            'expiration_date' => fake()->dateTimeBetween('-5 years', '-1 day')->format('Y-m-d'),
        ]);
    }

    public function underRenewal(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'under_renewal',
            'expiration_date' => fake()->dateTimeBetween('-30 days', '+30 days')->format('Y-m-d'),
        ]);
    }
}

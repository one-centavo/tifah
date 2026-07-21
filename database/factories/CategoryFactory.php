<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'description' => fake()->sentence(),
            'is_cold_chain' => false,
            'is_special_control' => false,
            'created_by' => User::factory(),
            'updated_by' => null,
            'deleted_by' => null,
        ];
    }

    public function coldChain(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_cold_chain' => true,
        ]);
    }

    public function specialControl(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_special_control' => true,
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\ConcentrationUnit;
use App\Models\Container;
use App\Models\ContentUnit;
use App\Models\Laboratory;
use App\Models\Medicine;
use App\Models\SanitaryRegistry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Medicine>
 */
class MedicineFactory extends Factory
{
    protected $model = Medicine::class;

    public function definition(): array
    {
        $laboratoryId = Laboratory::factory();

        return [
            'category_id' => Category::factory(),
            'laboratory_id' => $laboratoryId,
            'sanitary_registry_id' => function (array $attributes) use ($laboratoryId) {
                return SanitaryRegistry::factory()->create([
                    'laboratory_id' => $attributes['laboratory_id'] ?? $laboratoryId,
                ])->id;
            },
            'name' => fake()->unique()->words(2, true),
            'generic_name' => fake()->words(2, true),
            'concentration_value' => fake()->randomFloat(2, 1, 1000),
            'concentration_unit_id' => ConcentrationUnit::factory(),
            'container_id' => Container::factory(),
            'content_quantity' => fake()->numberBetween(1, 100),
            'content_unit_id' => ContentUnit::factory(),
            'is_cold_chain' => false,
            'is_special_control' => false,
            'min_stock' => fake()->numberBetween(5, 20),
            'selling_price' => fake()->randomFloat(2, 1000, 200000),
            'description' => fake()->sentence(),
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

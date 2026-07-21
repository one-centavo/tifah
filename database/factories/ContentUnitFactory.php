<?php

namespace Database\Factories;

use App\Models\ContentUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContentUnit>
 */
class ContentUnitFactory extends Factory
{
    protected $model = ContentUnit::class;

    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\ConcentrationUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConcentrationUnit>
 */
class ConcentrationUnitFactory extends Factory
{
    protected $model = ConcentrationUnit::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word().' '.$this->faker->unique()->word(),
            'symbol' => $this->faker->unique()->lexify('???'),
        ];
    }
}

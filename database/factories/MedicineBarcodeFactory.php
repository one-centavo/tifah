<?php

namespace Database\Factories;

use App\Models\Medicine;
use App\Models\MedicineBarcode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MedicineBarcodeFactory extends Factory
{
    protected $model = MedicineBarcode::class;

    public function definition(): array
    {
        return [
            'medicine_id' => Medicine::factory(),
            'barcode' => fake()->unique()->numerify('##########'),
            'is_main' => false,
            'created_by' => User::factory(),
            'updated_by' => null,
            'deleted_by' => null,
        ];
    }
}

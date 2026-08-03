<?php

namespace Database\Seeders;

use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create();

        $suppliers = [
            'Copidrogas',
            'Nacional de Drogas',
            'Depósito de Drogas del Llano',
            'Éticos Serrano Gómez',
            'Audifarma',
            'Cruz Verde',
            'Droguerías Acuña',
            'Drosan',
            'Distribuidora Farmacéutica de Colombia',
            'Lafam',
        ];

        foreach ($suppliers as $name) {
            if (!Supplier::where('name', $name)->exists()) {
                Supplier::factory()->create([
                    'name' => $name,
                    'created_by' => $user->id,
                ]);
            }
        }
    }
}

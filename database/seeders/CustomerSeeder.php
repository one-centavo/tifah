<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create();

        $customers = [
            'Farmacia Cruz Verde Centro',
            'Droguería La Rebaja Calle 19',
            'Farmatodo Calle 100',
            'Droguerías Cafam El Lago',
            'Droguería Colsubsidio Portal',
            'Farmacia Pasteur Laureles',
            'Droguerías Acuña Chapinero',
            'Droguería Alemana Principal',
            'Droguería San Jorge',
            'Farmacia Cruz Azul',
        ];

        foreach ($customers as $name) {
            if (! Customer::where('name', $name)->exists()) {
                Customer::factory()->create([
                    'name' => $name,
                    'created_by' => $user->id,
                ]);
            }
        }
    }
}

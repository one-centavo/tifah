<?php

namespace Database\Seeders;

use App\Models\Container;
use Illuminate\Database\Seeder;

class ContainerSeeder extends Seeder
{
    public function run(): void
    {
        $containers = [
            'Frasco',
            'Caja',
            'Ampolleta',
            'Blíster',
            'Tubo',
            'Sobre',
            'Gotero',
            'Jeringa Prellenada',
            'Bolsa',
            'Inhalador',
            'Tarro',
            'Vial',
        ];

        foreach ($containers as $name) {
            Container::firstOrCreate(['name' => $name]);
        }
    }
}

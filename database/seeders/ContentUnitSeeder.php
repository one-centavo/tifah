<?php

namespace Database\Seeders;

use App\Models\ContentUnit;
use Illuminate\Database\Seeder;

class ContentUnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            'Mililitro',
            'Litro',
            'Tableta',
            'Cápsula',
            'Sobre',
            'Gota',
            'Ampolla',
            'Óvulo',
            'Supositorio',
            'Gramo',
            'Dosis',
            'Parche',
            'Unidad',
        ];

        foreach ($units as $name) {
            ContentUnit::firstOrCreate(['name' => $name]);
        }
    }
}

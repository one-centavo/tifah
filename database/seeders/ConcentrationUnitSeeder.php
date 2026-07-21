<?php

namespace Database\Seeders;

use App\Models\ConcentrationUnit;
use Illuminate\Database\Seeder;

class ConcentrationUnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            [
                'name' => 'Miligramo',
                'symbol' => 'mg',
            ],
            [
                'name' => 'Gramo',
                'symbol' => 'g',
            ],
            [
                'name' => 'Microgramo',
                'symbol' => 'mcg',
            ],
            [
                'name' => 'Unidad Internacional',
                'symbol' => 'UI',
            ],
            [
                'name' => 'Porcentaje',
                'symbol' => '%',
            ],
            [
                'name' => 'Miligramo por Mililitro',
                'symbol' => 'mg/ml',
            ],
            [
                'name' => 'Microgramo por Mililitro',
                'symbol' => 'mcg/ml',
            ],
            [
                'name' => 'Unidad por Mililitro',
                'symbol' => 'U/ml',
            ],
            [
                'name' => 'Miliequivalente',
                'symbol' => 'mEq',
            ],
            [
                'name' => 'Mililitro',
                'symbol' => 'ml',
            ],
            [
                'name' => 'Litro',
                'symbol' => 'l',
            ],
            [
                'name' => 'Porcentaje peso/volumen',
                'symbol' => '% w/v',
            ],
            [
                'name' => 'Porcentaje peso/peso',
                'symbol' => '% w/w',
            ],
            [
                'name' => 'Microgramo por dosis',
                'symbol' => 'mcg/dose',
            ],
            [
                'name' => 'Miligramo por dosis',
                'symbol' => 'mg/dose',
            ],
            [
                'name' => 'Partes por millón',
                'symbol' => 'ppm',
            ],
            [
                'name' => 'Unidad',
                'symbol' => 'U',
            ],
            [
                'name' => 'Miligramo por gramo',
                'symbol' => 'mg/g',
            ],
        ];

        foreach ($units as $unit) {
            ConcentrationUnit::firstOrCreate(
                ['symbol' => $unit['symbol']],
                ['name' => $unit['name']]
            );
        }
    }
}

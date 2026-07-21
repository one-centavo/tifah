<?php

namespace Database\Seeders;

use App\Models\Laboratory;
use App\Models\User;
use Illuminate\Database\Seeder;

class LaboratorySeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create();

        $laboratories = [
            [
                'name' => 'Pfizer',
                'description' => 'Laboratorio multinacional líder en innovación y desarrollo de vacunas y medicamentos especializados.',
            ],
            [
                'name' => 'Bayer',
                'description' => 'Compañía farmacéutica internacional enfocada en medicamentos de venta libre, cardiología y salud femenina.',
            ],
            [
                'name' => 'Roche',
                'description' => 'Líder mundial en productos de diagnóstico y tratamientos oncológicos avanzados.',
            ],
            [
                'name' => 'Novartis',
                'description' => 'Desarrollador multinacional de medicamentos innovadores y soluciones de cuidado de salud ocular.',
            ],
            [
                'name' => 'Sanofi',
                'description' => 'Especializado en vacunas, tratamientos para la diabetes y enfermedades raras.',
            ],
            [
                'name' => 'AstraZeneca',
                'description' => 'Enfocado en oncología, enfermedades cardiovasculares, renales, metabólicas y respiratorias.',
            ],
            [
                'name' => 'Genfar',
                'description' => 'Uno de los mayores fabricantes de medicamentos genéricos de alta calidad y accesibles en Latinoamérica.',
            ],
            [
                'name' => 'Tecnoquímicas',
                'description' => 'Grupo empresarial líder de la industria farmacéutica en Colombia y la región andina.',
            ],
            [
                'name' => 'Procaps',
                'description' => 'Especialista en cápsulas de gelatina blanda y desarrollo de formulaciones avanzadas.',
            ],
            [
                'name' => 'Abbott',
                'description' => 'Empresa farmacéutica de dispositivos médicos y productos nutricionales y diagnósticos.',
            ],
            [
                'name' => 'GlaxoSmithKline (GSK)',
                'description' => 'Investigador y fabricante de vacunas, medicamentos para el VIH y salud respiratoria.',
            ],
            [
                'name' => 'MK',
                'description' => 'Marca líder de medicamentos genéricos de confianza con amplio portafolio en la región.',
            ],
        ];

        foreach ($laboratories as $laboratory) {
            Laboratory::firstOrCreate(
                ['name' => $laboratory['name']],
                [
                    'description' => $laboratory['description'],
                    'created_by' => $user->id,
                ]
            );
        }
    }
}

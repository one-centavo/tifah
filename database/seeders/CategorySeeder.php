<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create();

        $categories = [
            [
                'name' => 'Analgésicos y Antiinflamatorios',
                'description' => 'Medicamentos para el alivio del dolor y la inflamación.',
                'is_cold_chain' => false,
                'is_special_control' => false,
            ],
            [
                'name' => 'Antibióticos y Antimicrobianos',
                'description' => 'Medicamentos para combatir infecciones bacterianas.',
                'is_cold_chain' => false,
                'is_special_control' => false,
            ],
            [
                'name' => 'Vacunas e Inmunológicos',
                'description' => 'Productos biológicos para generar inmunidad activa, requieren cadena de frío.',
                'is_cold_chain' => true,
                'is_special_control' => false,
            ],
            [
                'name' => 'Insulinas y Análogos',
                'description' => 'Hormonas para el control de la diabetes, requieren conservación en cadena de frío.',
                'is_cold_chain' => true,
                'is_special_control' => false,
            ],
            [
                'name' => 'Opioides y Estupefacientes',
                'description' => 'Medicamentos de control especial para dolor severo con alto riesgo de dependencia.',
                'is_cold_chain' => false,
                'is_special_control' => true,
            ],
            [
                'name' => 'Psicotrópicos y Sedantes',
                'description' => 'Medicamentos de control especial para trastornos del sistema nervioso central.',
                'is_cold_chain' => false,
                'is_special_control' => true,
            ],
            [
                'name' => 'Cardiovasculares',
                'description' => 'Medicamentos para el tratamiento de la presión arterial y afecciones cardíacas.',
                'is_cold_chain' => false,
                'is_special_control' => false,
            ],
            [
                'name' => 'Dermatológicos',
                'description' => 'Cremas, ungüentos y geles para el cuidado y tratamiento de afecciones de la piel.',
                'is_cold_chain' => false,
                'is_special_control' => false,
            ],
            [
                'name' => 'Antivirales',
                'description' => 'Medicamentos para el tratamiento de infecciones causadas por virus.',
                'is_cold_chain' => false,
                'is_special_control' => false,
            ],
            [
                'name' => 'Antihistamínicos',
                'description' => 'Medicamentos para el tratamiento de alergias y reacciones de hipersensibilidad.',
                'is_cold_chain' => false,
                'is_special_control' => false,
            ],
            [
                'name' => 'Oncológicos',
                'description' => 'Medicamentos especializados para el tratamiento del cáncer.',
                'is_cold_chain' => false,
                'is_special_control' => false,
            ],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(
                ['name' => $category['name']],
                [
                    'description' => $category['description'],
                    'is_cold_chain' => $category['is_cold_chain'],
                    'is_special_control' => $category['is_special_control'],
                    'created_by' => $user->id,
                ]
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\ConcentrationUnit;
use App\Models\Container;
use App\Models\ContentUnit;
use App\Models\Laboratory;
use App\Models\Medicine;
use App\Models\SanitaryRegistry;
use App\Models\User;
use Illuminate\Database\Seeder;

class MedicineSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create();

        $medicines = [
            [
                'name' => 'Acetaminofén MK',
                'generic_name' => 'Acetaminofén',
                'category_name' => 'Analgésicos y Antiinflamatorios',
                'laboratory_name' => 'MK',
                'concentration_value' => 500,
                'concentration_unit_symbol' => 'mg',
                'container_name' => 'Caja',
                'content_quantity' => 30,
                'content_unit_name' => 'Tableta',
                'is_cold_chain' => false,
                'is_special_control' => false,
                'selling_price' => 5200.00,
                'description' => 'Analgésico y antipirético para el alivio del dolor leve a moderado y la fiebre.',
            ],
            [
                'name' => 'Ibuprofeno Genfar',
                'generic_name' => 'Ibuprofeno',
                'category_name' => 'Analgésicos y Antiinflamatorios',
                'laboratory_name' => 'Genfar',
                'concentration_value' => 400,
                'concentration_unit_symbol' => 'mg',
                'container_name' => 'Caja',
                'content_quantity' => 20,
                'content_unit_name' => 'Tableta',
                'is_cold_chain' => false,
                'is_special_control' => false,
                'selling_price' => 8400.00,
                'description' => 'Antiinflamatorio no esteroideo indicado para el alivio del dolor y la inflamación.',
            ],
            [
                'name' => 'Amoxicilina Genfar',
                'generic_name' => 'Amoxicilina',
                'category_name' => 'Antibióticos y Antimicrobianos',
                'laboratory_name' => 'Genfar',
                'concentration_value' => 500,
                'concentration_unit_symbol' => 'mg',
                'container_name' => 'Caja',
                'content_quantity' => 30,
                'content_unit_name' => 'Cápsula',
                'is_cold_chain' => false,
                'is_special_control' => false,
                'selling_price' => 12500.00,
                'description' => 'Antibiótico de amplio espectro para el tratamiento de infecciones bacterianas susceptibles.',
            ],
            [
                'name' => 'Losartán Potásico MK',
                'generic_name' => 'Losartán Potásico',
                'category_name' => 'Cardiovasculares',
                'laboratory_name' => 'MK',
                'concentration_value' => 50,
                'concentration_unit_symbol' => 'mg',
                'container_name' => 'Caja',
                'content_quantity' => 30,
                'content_unit_name' => 'Tableta',
                'is_cold_chain' => false,
                'is_special_control' => false,
                'selling_price' => 14800.00,
                'description' => 'Antihipertensivo indicado para el tratamiento de la hipertensión arterial.',
            ],
            [
                'name' => 'Atorvastatina Tecnoquímicas',
                'generic_name' => 'Atorvastatina',
                'category_name' => 'Cardiovasculares',
                'laboratory_name' => 'Tecnoquímicas',
                'concentration_value' => 20,
                'concentration_unit_symbol' => 'mg',
                'container_name' => 'Caja',
                'content_quantity' => 30,
                'content_unit_name' => 'Tableta',
                'is_cold_chain' => false,
                'is_special_control' => false,
                'selling_price' => 24500.00,
                'description' => 'Medicamento hipolipemiante utilizado para reducir los niveles de colesterol y triglicéridos.',
            ],
            [
                'name' => 'Loratadina MK',
                'generic_name' => 'Loratadina',
                'category_name' => 'Antihistamínicos',
                'laboratory_name' => 'MK',
                'concentration_value' => 10,
                'concentration_unit_symbol' => 'mg',
                'container_name' => 'Caja',
                'content_quantity' => 10,
                'content_unit_name' => 'Tableta',
                'is_cold_chain' => false,
                'is_special_control' => false,
                'selling_price' => 4200.00,
                'description' => 'Antihistamínico no sedante de acción prolongada para el alivio de los síntomas de alergia.',
            ],
            [
                'name' => 'Lantus Solostar',
                'generic_name' => 'Insulina Glargina',
                'category_name' => 'Insulinas y Análogos',
                'laboratory_name' => 'Sanofi',
                'concentration_value' => 100,
                'concentration_unit_symbol' => 'U/ml',
                'container_name' => 'Vial',
                'content_quantity' => 3,
                'content_unit_name' => 'Mililitro',
                'is_cold_chain' => true,
                'is_special_control' => false,
                'selling_price' => 115000.00,
                'description' => 'Insulina basal de acción prolongada para el control de la diabetes mellitus.',
            ],
            [
                'name' => 'Vaxigrip',
                'generic_name' => 'Vacuna de Influenza',
                'category_name' => 'Vacunas e Inmunológicos',
                'laboratory_name' => 'Sanofi',
                'concentration_value' => 15,
                'concentration_unit_symbol' => 'mcg/dose',
                'container_name' => 'Jeringa Prellenada',
                'content_quantity' => 1,
                'content_unit_name' => 'Dosis',
                'is_cold_chain' => true,
                'is_special_control' => false,
                'selling_price' => 48000.00,
                'description' => 'Vacuna de virus fraccionados contra la influenza estacional.',
            ],
            [
                'name' => 'Tramadol Tecnoquímicas',
                'generic_name' => 'Tramadol Clorhidrato',
                'category_name' => 'Opioides y Estupefacientes',
                'laboratory_name' => 'Tecnoquímicas',
                'concentration_value' => 100,
                'concentration_unit_symbol' => 'mg/ml',
                'container_name' => 'Gotero',
                'content_quantity' => 10,
                'content_unit_name' => 'Mililitro',
                'is_cold_chain' => false,
                'is_special_control' => true,
                'selling_price' => 18500.00,
                'description' => 'Analgésico opioide potente para el tratamiento del dolor moderado a severo.',
            ],
            [
                'name' => 'Rivotril',
                'generic_name' => 'Clonazepam',
                'category_name' => 'Psicotrópicos y Sedantes',
                'laboratory_name' => 'Roche',
                'concentration_value' => 2,
                'concentration_unit_symbol' => 'mg',
                'container_name' => 'Caja',
                'content_quantity' => 30,
                'content_unit_name' => 'Tableta',
                'is_cold_chain' => false,
                'is_special_control' => true,
                'selling_price' => 12000.00,
                'description' => 'Anticonvulsivante y ansiolítico indicado en trastornos de pánico y ansiedad.',
            ],
            [
                'name' => 'Clotrimazol Crema Bayer',
                'generic_name' => 'Clotrimazol',
                'category_name' => 'Dermatológicos',
                'laboratory_name' => 'Bayer',
                'concentration_value' => 1,
                'concentration_unit_symbol' => '%',
                'container_name' => 'Tubo',
                'content_quantity' => 40,
                'content_unit_name' => 'Gramo',
                'is_cold_chain' => false,
                'is_special_control' => false,
                'selling_price' => 9500.00,
                'description' => 'Antimicótico de uso tópico indicado para el tratamiento de infecciones fúngicas cutáneas.',
            ],
            [
                'name' => 'Aciclovir Genfar',
                'generic_name' => 'Aciclovir',
                'category_name' => 'Antivirales',
                'laboratory_name' => 'Genfar',
                'concentration_value' => 200,
                'concentration_unit_symbol' => 'mg',
                'container_name' => 'Caja',
                'content_quantity' => 25,
                'content_unit_name' => 'Tableta',
                'is_cold_chain' => false,
                'is_special_control' => false,
                'selling_price' => 15200.00,
                'description' => 'Antiviral indicado en el tratamiento de infecciones por virus herpes simple.',
            ],
        ];

        foreach ($medicines as $med) {
            $category = Category::where('name', $med['category_name'])->first()
                ?? Category::factory()->create(['name' => $med['category_name'], 'created_by' => $user->id]);

            $laboratory = Laboratory::where('name', $med['laboratory_name'])->first()
                ?? Laboratory::factory()->create(['name' => $med['laboratory_name'], 'created_by' => $user->id]);

            $registry = SanitaryRegistry::where('laboratory_id', $laboratory->id)->first()
                ?? SanitaryRegistry::factory()->create(['laboratory_id' => $laboratory->id, 'created_by' => $user->id]);

            $concentrationUnit = ConcentrationUnit::where('symbol', $med['concentration_unit_symbol'])->first()
                ?? ConcentrationUnit::factory()->create(['symbol' => $med['concentration_unit_symbol'], 'name' => $med['concentration_unit_symbol']]);

            $container = Container::where('name', $med['container_name'])->first()
                ?? Container::factory()->create(['name' => $med['container_name']]);

            $contentUnit = ContentUnit::where('name', $med['content_unit_name'])->first()
                ?? ContentUnit::factory()->create(['name' => $med['content_unit_name']]);

            Medicine::firstOrCreate(
                ['name' => $med['name']],
                [
                    'category_id' => $category->id,
                    'laboratory_id' => $laboratory->id,
                    'sanitary_registry_id' => $registry->id,
                    'generic_name' => $med['generic_name'],
                    'concentration_value' => $med['concentration_value'],
                    'concentration_unit_id' => $concentrationUnit->id,
                    'container_id' => $container->id,
                    'content_quantity' => $med['content_quantity'],
                    'content_unit_id' => $contentUnit->id,
                    'is_cold_chain' => $med['is_cold_chain'],
                    'is_special_control' => $med['is_special_control'],
                    'min_stock' => 5,
                    'selling_price' => $med['selling_price'],
                    'description' => $med['description'],
                    'created_by' => $user->id,
                ]
            );
        }
    }
}

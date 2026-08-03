<?php

namespace Database\Seeders;

use App\Models\Medicine;
use App\Models\MedicineBarcode;
use App\Models\User;
use Illuminate\Database\Seeder;

class MedicineBarcodeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create();
        $medicines = Medicine::all();

        if ($medicines->isEmpty()) {
            $medicines = Medicine::factory()->count(10)->create(['created_by' => $user->id]);
        }

        foreach ($medicines as $medicine) {
            // Seed main barcode
            MedicineBarcode::factory()->main()->create([
                'medicine_id' => $medicine->id,
                'created_by' => $user->id,
            ]);

            // Optionally seed 1 or 2 secondary barcodes
            $additionalCount = rand(0, 2);
            if ($additionalCount > 0) {
                MedicineBarcode::factory()->count($additionalCount)->create([
                    'medicine_id' => $medicine->id,
                    'created_by' => $user->id,
                ]);
            }
        }
    }
}

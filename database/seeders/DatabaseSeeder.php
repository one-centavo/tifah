<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command->warn('Skipping test user creation in production environment.');
        } else {
            User::factory()->create([
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => 'test@example.com',
                'role' => 'admin',
            ]);
        }

        $this->call([
            CategorySeeder::class,
            SupplierSeeder::class,
            LaboratorySeeder::class,
            SanitaryRegistrySeeder::class,
            ConcentrationUnitSeeder::class,
            ContainerSeeder::class,
            ContentUnitSeeder::class,
            MedicineSeeder::class,
            MedicineBarcodeSeeder::class,
            PurchaseOrderSeeder::class,
            LotSeeder::class,
            CustomerSeeder::class,
        ]);
    }
}

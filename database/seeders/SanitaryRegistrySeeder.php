<?php

namespace Database\Seeders;

use App\Models\Laboratory;
use App\Models\SanitaryRegistry;
use App\Models\User;
use Illuminate\Database\Seeder;

class SanitaryRegistrySeeder extends Seeder
{
    public function run(): void
    {
        $laboratories = Laboratory::all();
        $user = User::first() ?? User::factory()->create();

        if ($laboratories->isEmpty()) {
            $laboratories = Laboratory::factory()->count(5)->create(['created_by' => $user->id]);
        }

        foreach ($laboratories as $laboratory) {
            // Seed a valid registry
            SanitaryRegistry::factory()->create([
                'laboratory_id' => $laboratory->id,
                'status' => 'valid',
                'expiration_date' => now()->addYears(rand(2, 5))->format('Y-m-d'),
                'created_by' => $user->id,
            ]);

            // Optionally seed another one that might be expired or under renewal
            if (rand(0, 1)) {
                $status = rand(0, 1) ? 'expired' : 'under_renewal';
                $expirationDate = $status === 'expired'
                    ? now()->subMonths(rand(1, 24))->format('Y-m-d')
                    : now()->addDays(rand(-15, 30))->format('Y-m-d');

                SanitaryRegistry::factory()->create([
                    'laboratory_id' => $laboratory->id,
                    'status' => $status,
                    'expiration_date' => $expirationDate,
                    'created_by' => $user->id,
                ]);
            }
        }
    }
}

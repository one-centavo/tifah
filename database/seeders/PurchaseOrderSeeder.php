<?php

namespace Database\Seeders;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

class PurchaseOrderSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create();
        $suppliers = Supplier::all();

        if ($suppliers->isEmpty()) {
            $suppliers = Supplier::factory()->count(5)->create(['created_by' => $user->id]);
        }

        foreach ($suppliers as $supplier) {
            $count = rand(1, 2);
            for ($i = 0; $i < $count; $i++) {
                PurchaseOrder::factory()->create([
                    'supplier_id' => $supplier->id,
                    'created_by' => $user->id,
                ]);
            }
        }
    }
}

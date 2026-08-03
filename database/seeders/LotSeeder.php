<?php

namespace Database\Seeders;

use App\Models\Lot;
use App\Models\Medicine;
use App\Models\PurchaseOrder;
use App\Models\User;
use Illuminate\Database\Seeder;

class LotSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::first() ?? User::factory()->create();
        $medicines = Medicine::all();
        $purchaseOrders = PurchaseOrder::all();

        if ($medicines->isEmpty()) {
            return;
        }

        if ($purchaseOrders->isEmpty()) {
            return;
        }

        foreach ($medicines as $medicine) {
            $count = rand(1, 3);
            for ($i = 0; $i < $count; $i++) {
                $purchaseOrder = $purchaseOrders->random();
                Lot::factory()->create([
                    'medicine_id' => $medicine->id,
                    'purchase_order_id' => $purchaseOrder->id,
                    'created_by' => $user->id,
                ]);
            }
        }
    }
}

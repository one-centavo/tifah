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
                $lot = Lot::factory()->create([
                    'medicine_id' => $medicine->id,
                    'purchase_order_id' => $purchaseOrder->id,
                    'created_by' => $user->id,
                ]);

                \App\Models\InventoryMovement::create([
                    'lot_id' => $lot->id,
                    'type' => 'entry',
                    'quantity' => $lot->initial_quantity,
                    'previous_balance' => 0,
                    'new_balance' => $lot->initial_quantity,
                    'concept' => 'Merchandise reception - Batch '.$lot->batch_number,
                    'reference_id' => $purchaseOrder->id,
                    'created_by' => $user->id,
                ]);
            }
        }
    }
}

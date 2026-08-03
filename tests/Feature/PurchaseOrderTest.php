<?php

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\PurchaseOrderSeeder;
use Database\Seeders\SupplierSeeder;

it('can create a purchase order using factory', function () {
    $purchaseOrder = PurchaseOrder::factory()->create();

    expect($purchaseOrder)->toBeInstanceOf(PurchaseOrder::class);
    expect($purchaseOrder->supplier)->toBeInstanceOf(Supplier::class);
    expect($purchaseOrder->creator)->toBeInstanceOf(User::class);
});

it('can create a received purchase order using factory state', function () {
    $purchaseOrder = PurchaseOrder::factory()->received()->create();

    expect($purchaseOrder->status)->toBe('received');
    expect($purchaseOrder->received_at)->not->toBeNull();
});

it('can seed purchase orders using PurchaseOrderSeeder', function () {
    $this->seed(SupplierSeeder::class);
    $this->seed(PurchaseOrderSeeder::class);

    expect(PurchaseOrder::count())->toBeGreaterThan(0);
});

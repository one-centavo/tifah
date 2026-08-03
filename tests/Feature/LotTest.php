<?php

use App\Models\Lot;
use App\Models\Medicine;
use App\Models\PurchaseOrder;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

it('can create a lot using factory', function () {
    $lot = Lot::factory()->create();

    expect($lot)->toBeInstanceOf(Lot::class);
    expect($lot->medicine)->toBeInstanceOf(Medicine::class);
    expect($lot->purchaseOrder)->toBeInstanceOf(PurchaseOrder::class);
    expect($lot->creator)->toBeInstanceOf(User::class);
    expect($lot->status)->toBe('active');
});

it('can create a blocked lot using factory state', function () {
    $lot = Lot::factory()->blocked()->create();

    expect($lot->status)->toBe('blocked');
});

it('can create a damaged lot using factory state', function () {
    $lot = Lot::factory()->damaged()->create();

    expect($lot->status)->toBe('damaged');
    expect($lot->current_quantity)->toBe(0);
});

it('can seed lots using LotSeeder through DatabaseSeeder', function () {
    $this->seed(DatabaseSeeder::class);

    expect(Lot::count())->toBeGreaterThan(0);
});

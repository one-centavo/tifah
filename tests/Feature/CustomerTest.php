<?php

use App\Models\Customer;
use App\Models\User;
use Database\Seeders\CustomerSeeder;

it('can create a customer using factory', function () {
    $customer = Customer::factory()->create();

    expect($customer)->toBeInstanceOf(Customer::class);
    expect($customer->nit)->toHaveLength(9);
    expect($customer->dv)->toBeBetween(0, 9);
    expect($customer->creator)->toBeInstanceOf(User::class);
});

it('calculates the verification digit (dv) correctly', function () {
    // Test a known Colombian NIT and its DV
    // For NIT: 800197268, DV should be 4
    $customer = Customer::factory()->create([
        'nit' => '800197268',
    ]);

    expect($customer->dv)->toBe(4);

    // For NIT: 830999999, DV should be 0
    $customer2 = Customer::factory()->create([
        'nit' => '830999999',
    ]);

    expect($customer2->dv)->toBe(0);
});

it('can seed customers using CustomerSeeder', function () {
    $this->seed(CustomerSeeder::class);

    expect(Customer::count())->toBe(10);
    expect(Customer::where('name', 'Farmatodo Calle 100')->exists())->toBeTrue();
});

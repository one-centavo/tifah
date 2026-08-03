<?php

use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\SupplierSeeder;

it('can create a supplier using factory', function () {
    $supplier = Supplier::factory()->create();

    expect($supplier)->toBeInstanceOf(Supplier::class);
    expect($supplier->nit)->toHaveLength(9);
    expect($supplier->dv)->toBeBetween(0, 9);
    expect($supplier->creator)->toBeInstanceOf(User::class);
});

it('calculates the verification digit (dv) correctly', function () {
    // Test a known Colombian NIT and its DV
    // For NIT: 800197268, DV should be 4
    $supplier = Supplier::factory()->create([
        'nit' => '800197268',
    ]);

    expect($supplier->dv)->toBe(4);

    // For NIT: 830999999, let's verify its DV
    // Sum calculation:
    // 9*3 = 27
    // 9*7 = 63
    // 9*13 = 117
    // 9*17 = 153
    // 9*19 = 171
    // 9*23 = 207
    // 0*29 = 0
    // 3*37 = 111
    // 8*41 = 328
    // Total sum = 27 + 63 + 117 + 153 + 171 + 207 + 0 + 111 + 328 = 1177
    // 1177 % 11 = 0.
    // If remainder is 0, DV is 0.
    $supplier2 = Supplier::factory()->create([
        'nit' => '830999999',
    ]);

    expect($supplier2->dv)->toBe(0);
});

it('can seed suppliers using SupplierSeeder', function () {
    $this->seed(SupplierSeeder::class);

    expect(Supplier::count())->toBe(10);
    expect(Supplier::where('name', 'Copidrogas')->exists())->toBeTrue();
});

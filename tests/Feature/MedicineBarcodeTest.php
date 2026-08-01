<?php

use App\Models\Medicine;
use App\Models\MedicineBarcode;
use App\Models\User;
use Database\Seeders\MedicineBarcodeSeeder;

it('can create a medicine barcode using factory', function () {
    $barcode = MedicineBarcode::factory()->create();

    expect($barcode)->toBeInstanceOf(MedicineBarcode::class);
    expect($barcode->medicine)->toBeInstanceOf(Medicine::class);
    expect($barcode->creator)->toBeInstanceOf(User::class);
    expect($barcode->is_main)->toBeFalse();
});

it('can create a main medicine barcode using factory state', function () {
    $barcode = MedicineBarcode::factory()->main()->create();

    expect($barcode->is_main)->toBeTrue();
});

it('can seed medicine barcodes using MedicineBarcodeSeeder', function () {
    $user = User::factory()->create();
    $medicines = Medicine::factory()->count(3)->create(['created_by' => $user->id]);

    $this->seed(MedicineBarcodeSeeder::class);

    foreach ($medicines as $medicine) {
        $mainBarcodes = $medicine->barcodes()->where('is_main', true)->get();
        expect($mainBarcodes)->toHaveCount(1);

        $totalBarcodes = $medicine->barcodes()->count();
        expect($totalBarcodes)->toBeGreaterThanOrEqual(1)
            ->toBeLessThanOrEqual(3);
    }
});

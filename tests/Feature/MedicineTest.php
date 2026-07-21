<?php

use App\Models\Category;
use App\Models\ConcentrationUnit;
use App\Models\Container;
use App\Models\ContentUnit;
use App\Models\Laboratory;
use App\Models\Medicine;
use App\Models\SanitaryRegistry;
use App\Models\User;
use Database\Seeders\MedicineSeeder;

it('can create a medicine using factory', function () {
    $medicine = Medicine::factory()->create();

    expect($medicine)->toBeInstanceOf(Medicine::class);
    expect($medicine->category)->toBeInstanceOf(Category::class);
    expect($medicine->laboratory)->toBeInstanceOf(Laboratory::class);
    expect($medicine->sanitaryRegistry)->toBeInstanceOf(SanitaryRegistry::class);
    expect($medicine->concentrationUnit)->toBeInstanceOf(ConcentrationUnit::class);
    expect($medicine->container)->toBeInstanceOf(Container::class);
    expect($medicine->contentUnit)->toBeInstanceOf(ContentUnit::class);
    expect($medicine->creator)->toBeInstanceOf(User::class);
});

it('can create a cold chain medicine using factory state', function () {
    $medicine = Medicine::factory()->coldChain()->create();

    expect($medicine->is_cold_chain)->toBeTrue();
});

it('can create a special control medicine using factory state', function () {
    $medicine = Medicine::factory()->specialControl()->create();

    expect($medicine->is_special_control)->toBeTrue();
});

it('can seed medicines using MedicineSeeder', function () {
    $this->seed(MedicineSeeder::class);

    $this->assertDatabaseHas('medicines', [
        'name' => 'Acetaminofén MK',
        'generic_name' => 'Acetaminofén',
        'is_cold_chain' => false,
        'is_special_control' => false,
    ]);

    $this->assertDatabaseHas('medicines', [
        'name' => 'Lantus Solostar',
        'is_cold_chain' => true,
        'is_special_control' => false,
    ]);

    $this->assertDatabaseHas('medicines', [
        'name' => 'Rivotril',
        'is_cold_chain' => false,
        'is_special_control' => true,
    ]);

    expect(Medicine::count())->toBe(12);
});

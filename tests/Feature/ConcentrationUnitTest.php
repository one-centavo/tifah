<?php

use App\Models\ConcentrationUnit;
use Database\Seeders\ConcentrationUnitSeeder;

it('can create a concentration unit using factory', function () {
    $unit = ConcentrationUnit::factory()->create();

    expect($unit)->toBeInstanceOf(ConcentrationUnit::class);
    expect($unit->name)->toBeString();
    expect($unit->symbol)->toBeString();
});

it('can seed concentration units using ConcentrationUnitSeeder', function () {
    $this->seed(ConcentrationUnitSeeder::class);

    $this->assertDatabaseHas('concentration_units', [
        'name' => 'Miligramo',
        'symbol' => 'mg',
    ]);

    $this->assertDatabaseHas('concentration_units', [
        'name' => 'Mililitro',
        'symbol' => 'ml',
    ]);

    $this->assertDatabaseHas('concentration_units', [
        'name' => 'Litro',
        'symbol' => 'l',
    ]);

    expect(ConcentrationUnit::count())->toBe(18);
});

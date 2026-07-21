<?php

use App\Models\Laboratory;
use App\Models\User;
use Database\Seeders\LaboratorySeeder;

it('can create a laboratory using factory', function () {
    $laboratory = Laboratory::factory()->create();

    expect($laboratory)->toBeInstanceOf(Laboratory::class);
    expect($laboratory->name)->toBeString();
    expect($laboratory->creator)->toBeInstanceOf(User::class);
});

it('can seed laboratories using LaboratorySeeder', function () {
    $this->seed(LaboratorySeeder::class);

    $this->assertDatabaseHas('laboratories', [
        'name' => 'Pfizer',
    ]);

    $this->assertDatabaseHas('laboratories', [
        'name' => 'Genfar',
    ]);

    $this->assertDatabaseHas('laboratories', [
        'name' => 'Tecnoquímicas',
    ]);

    expect(Laboratory::count())->toBe(12);
});

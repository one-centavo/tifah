<?php

use App\Models\ContentUnit;
use Database\Seeders\ContentUnitSeeder;

it('can create a content unit using factory', function () {
    $unit = ContentUnit::factory()->create();

    expect($unit)->toBeInstanceOf(ContentUnit::class);
    expect($unit->name)->toBeString();
});

it('can seed content units using ContentUnitSeeder', function () {
    $this->seed(ContentUnitSeeder::class);

    $this->assertDatabaseHas('content_units', [
        'name' => 'Mililitro',
    ]);

    $this->assertDatabaseHas('content_units', [
        'name' => 'Tableta',
    ]);

    $this->assertDatabaseHas('content_units', [
        'name' => 'Cápsula',
    ]);

    expect(ContentUnit::count())->toBe(13);
});

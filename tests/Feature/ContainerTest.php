<?php

use App\Models\Container;
use Database\Seeders\ContainerSeeder;

it('can create a container using factory', function () {
    $container = Container::factory()->create();

    expect($container)->toBeInstanceOf(Container::class);
    expect($container->name)->toBeString();
});

it('can seed containers using ContainerSeeder', function () {
    $this->seed(ContainerSeeder::class);

    $this->assertDatabaseHas('containers', [
        'name' => 'Frasco',
    ]);

    $this->assertDatabaseHas('containers', [
        'name' => 'Blíster',
    ]);

    $this->assertDatabaseHas('containers', [
        'name' => 'Caja',
    ]);

    expect(Container::count())->toBe(12);
});

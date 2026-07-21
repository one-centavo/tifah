<?php

use App\Models\Category;
use App\Models\User;
use Database\Seeders\CategorySeeder;

it('can create a category using factory', function () {
    $category = Category::factory()->create();

    expect($category)->toBeInstanceOf(Category::class);
    expect($category->name)->toBeString();
    expect($category->is_cold_chain)->toBeFalse();
    expect($category->is_special_control)->toBeFalse();
    expect($category->creator)->toBeInstanceOf(User::class);
});

it('can create a cold chain category using factory state', function () {
    $category = Category::factory()->coldChain()->create();

    expect($category->is_cold_chain)->toBeTrue();
});

it('can create a special control category using factory state', function () {
    $category = Category::factory()->specialControl()->create();

    expect($category->is_special_control)->toBeTrue();
});

it('can seed categories using CategorySeeder', function () {
    $this->seed(CategorySeeder::class);

    $this->assertDatabaseHas('categories', [
        'name' => 'Analgésicos y Antiinflamatorios',
        'is_cold_chain' => false,
        'is_special_control' => false,
    ]);

    $this->assertDatabaseHas('categories', [
        'name' => 'Vacunas e Inmunológicos',
        'is_cold_chain' => true,
        'is_special_control' => false,
    ]);

    $this->assertDatabaseHas('categories', [
        'name' => 'Opioides y Estupefacientes',
        'is_cold_chain' => false,
        'is_special_control' => true,
    ]);

    expect(Category::count())->toBe(11);
});

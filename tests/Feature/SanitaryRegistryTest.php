<?php

use App\Models\Laboratory;
use App\Models\SanitaryRegistry;
use App\Models\User;
use Database\Seeders\LaboratorySeeder;
use Database\Seeders\SanitaryRegistrySeeder;

it('can create a sanitary registry using factory', function () {
    $registry = SanitaryRegistry::factory()->create();

    expect($registry)->toBeInstanceOf(SanitaryRegistry::class);
    expect($registry->registration_number)->toStartWith('INVIMA ');
    expect($registry->status)->toBe('valid');
    expect($registry->expiration_date)->toBeGreaterThan(now()->format('Y-m-d'));
    expect($registry->laboratory)->toBeInstanceOf(Laboratory::class);
    expect($registry->creator)->toBeInstanceOf(User::class);
});

it('can create an expired sanitary registry using factory state', function () {
    $registry = SanitaryRegistry::factory()->expired()->create();

    expect($registry->status)->toBe('expired');
    expect($registry->expiration_date)->toBeLessThan(now()->format('Y-m-d'));
});

it('can create an under renewal sanitary registry using factory state', function () {
    $registry = SanitaryRegistry::factory()->underRenewal()->create();

    expect($registry->status)->toBe('under_renewal');
});

it('can seed sanitary registries using SanitaryRegistrySeeder', function () {
    // We need laboratories seeded first since SanitaryRegistrySeeder depends on them
    $this->seed(LaboratorySeeder::class);
    $this->seed(SanitaryRegistrySeeder::class);

    $labsCount = Laboratory::count();
    $registriesCount = SanitaryRegistry::count();

    // Each laboratory should have at least 1 sanitary registry seeded
    expect($registriesCount)->toBeGreaterThanOrEqual($labsCount);
    expect(SanitaryRegistry::first()->registration_number)->toStartWith('INVIMA ');
});

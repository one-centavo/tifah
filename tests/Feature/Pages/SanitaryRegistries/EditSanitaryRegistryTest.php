<?php

declare(strict_types=1);

use App\Models\Laboratory;
use App\Models\SanitaryRegistry;
use App\Models\User;
use Livewire\Volt\Volt;

test('guest users are redirected to login page from edit', function () {
    $registry = SanitaryRegistry::factory()->create();
    $response = $this->get(route('sanitary-registries.edit', $registry));

    $response->assertRedirect('/login');
});

test('authorized users can access sanitary registries edit page', function () {
    $user = User::factory()->create();
    $registry = SanitaryRegistry::factory()->create();

    $this->actingAs($user);

    $response = $this->get(route('sanitary-registries.edit', $registry));

    $response
        ->assertOk()
        ->assertSeeVolt('sanitary-registries.edit');
});

test('it loads the sanitary registry data into the form', function () {
    $user = User::factory()->create();
    $lab = Laboratory::factory()->create(['name' => 'Tecnoquímicas']);
    $registry = SanitaryRegistry::factory()->create([
        'registration_number' => 'INVIMA 2026M-5555555',
        'laboratory_id' => $lab->id,
        'expiration_date' => '2030-12-31',
        'status' => 'valid',
        'description' => 'Original description',
    ]);

    $this->actingAs($user);

    $component = Volt::test('sanitary-registries.edit', ['sanitary_registry' => $registry]);

    expect($component->get('registration_number'))->toBe('INVIMA 2026M-5555555')
        ->and($component->get('laboratory_id'))->toBe($lab->id)
        ->and($component->get('expiration_date'))->toBe('2030-12-31')
        ->and($component->get('status'))->toBe('valid')
        ->and($component->get('description'))->toBe('Original description')
        ->and($component->get('laboratorySearch'))->toBe('Tecnoquímicas');
});

test('it validates registration number uniqueness against other registries except itself', function () {
    $user = User::factory()->create();
    $registry1 = SanitaryRegistry::factory()->create(['registration_number' => 'INVIMA 2026M-1111111']);
    $registry2 = SanitaryRegistry::factory()->create(['registration_number' => 'INVIMA 2026M-2222222']);

    $this->actingAs($user);

    // 1. Keeping its own name should pass
    $component = Volt::test('sanitary-registries.edit', ['sanitary_registry' => $registry1])
        ->call('save');

    $component->assertHasNoErrors();

    // 2. Using another registration number should fail
    $component = Volt::test('sanitary-registries.edit', ['sanitary_registry' => $registry1])
        ->set('registration_number', 'INVIMA 2026M-2222222')
        ->call('save');

    $component
        ->assertHasErrors(['registration_number' => 'unique'])
        ->assertSee('Este número de registro sanitario ya se encuentra registrado.');
});

test('it does not require future date on edit', function () {
    $user = User::factory()->create();
    $pastDate = now()->subMonth()->format('Y-m-d');

    // Create an expired registry
    $registry = SanitaryRegistry::factory()->create([
        'expiration_date' => $pastDate,
        'status' => 'expired',
    ]);

    $this->actingAs($user);

    // Save without changing the past date - should pass
    $component = Volt::test('sanitary-registries.edit', ['sanitary_registry' => $registry])
        ->set('description', 'Updated description of expired registry')
        ->call('save');

    $component->assertHasNoErrors();

    $this->assertDatabaseHas('sanitary_registries', [
        'id' => $registry->id,
        'expiration_date' => $pastDate,
        'description' => 'Updated description of expired registry',
    ]);
});

test('it successfully updates the sanitary registry and redirects', function () {
    $user = User::factory()->create();
    $originalCreator = User::factory()->create();
    $lab = Laboratory::factory()->create(['name' => 'Roche']);

    $registry = SanitaryRegistry::factory()->create([
        'registration_number' => 'INVIMA 2026M-3333333',
        'laboratory_id' => $lab->id,
        'expiration_date' => '2028-05-15',
        'status' => 'valid',
        'created_by' => $originalCreator->id,
    ]);

    $this->actingAs($user);

    $component = Volt::test('sanitary-registries.edit', ['sanitary_registry' => $registry])
        ->set('registration_number', 'invima 2026m-4444444') // lowercase to test uppercase normalization
        ->set('description', 'Updated description')
        ->call('save');

    $component
        ->assertHasNoErrors()
        ->assertRedirect(route('sanitary-registries.index'));

    $this->assertDatabaseHas('sanitary_registries', [
        'id' => $registry->id,
        'registration_number' => 'INVIMA 2026M-4444444', // normalized uppercase
        'description' => 'Updated description',
        'created_by' => $originalCreator->id, // remains unchanged
        'updated_by' => $user->id, // sets modifier
    ]);

    expect(session('success'))->toBe('El registro sanitario ha sido actualizado con éxito.');
});

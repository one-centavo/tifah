<?php

declare(strict_types=1);

use App\Models\Laboratory;
use App\Models\SanitaryRegistry;
use App\Models\User;
use Livewire\Volt\Volt;

test('guest users are redirected to login page from create', function () {
    $response = $this->get(route('sanitary-registries.create'));

    $response->assertRedirect('/login');
});

test('authorized users can access sanitary registries creation page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get(route('sanitary-registries.create'));

    $response
        ->assertOk()
        ->assertSeeVolt('sanitary-registries.create')
        ->assertSee(route('sanitary-registries.index'));
});

test('it validates registration number is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('sanitary-registries.create')
        ->set('registration_number', '')
        ->call('save');

    $component
        ->assertHasErrors(['registration_number' => 'required'])
        ->assertSee('El número de registro sanitario es obligatorio.');
});

test('it validates registration number matches regex format', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('sanitary-registries.create')
        ->set('registration_number', 'INVALID-FORMAT')
        ->call('save');

    $component
        ->assertHasErrors(['registration_number' => 'regex'])
        ->assertSee('El formato del número de registro sanitario es inválido. Debe ser como INVIMA 2026M-1234567.');
});

test('it validates registration number is unique', function () {
    $user = User::factory()->create();
    SanitaryRegistry::factory()->create(['registration_number' => 'INVIMA 2026M-1111111']);

    $this->actingAs($user);

    $component = Volt::test('sanitary-registries.create')
        ->set('registration_number', 'INVIMA 2026M-1111111')
        ->call('save');

    $component
        ->assertHasErrors(['registration_number' => 'unique'])
        ->assertSee('Este número de registro sanitario ya se encuentra registrado.');
});

test('it validates laboratory id is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('sanitary-registries.create')
        ->set('laboratory_id', null)
        ->call('save');

    $component
        ->assertHasErrors(['laboratory_id' => 'required'])
        ->assertSee('El laboratorio fabricante es obligatorio.');
});

test('it validates expiration date is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('sanitary-registries.create')
        ->set('expiration_date', '')
        ->call('save');

    $component
        ->assertHasErrors(['expiration_date' => 'required'])
        ->assertSee('La fecha de vencimiento es obligatoria.');
});

test('it validates expiration date is in the future on create', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    // Set to yesterday
    $yesterday = now()->subDay()->format('Y-m-d');

    $component = Volt::test('sanitary-registries.create')
        ->set('expiration_date', $yesterday)
        ->call('save');

    $component
        ->assertHasErrors(['expiration_date' => 'after'])
        ->assertSee('La fecha de vencimiento debe ser posterior a la fecha actual.');
});

test('it can search and select a laboratory', function () {
    $user = User::factory()->create();
    $lab = Laboratory::factory()->create(['name' => 'Laboratorio Genfar']);

    $this->actingAs($user);

    $component = Volt::test('sanitary-registries.create')
        ->set('laboratorySearch', 'Genfar')
        ->assertSee('Laboratorio Genfar')
        ->call('selectLaboratory', $lab->id, $lab->name);

    expect($component->get('laboratory_id'))->toBe($lab->id)
        ->and($component->get('laboratorySearch'))->toBe('Laboratorio Genfar')
        ->and($component->get('showLaboratoriesDropdown'))->toBeFalse();
});

test('it successfully creates a sanitary registry, normalizes number to uppercase, and redirects', function () {
    $user = User::factory()->create();
    $lab = Laboratory::factory()->create(['name' => 'Bayer']);

    $this->actingAs($user);

    $futureDate = now()->addYear()->format('Y-m-d');

    $component = Volt::test('sanitary-registries.create')
        ->set('registration_number', 'invima 2026m-9876543') // Lowercase input
        ->set('laboratory_id', $lab->id)
        ->set('expiration_date', $futureDate)
        ->set('status', 'valid')
        ->set('description', 'Notas del registro.')
        ->call('save');

    $component
        ->assertHasNoErrors()
        ->assertRedirect(route('sanitary-registries.index'));

    $this->assertDatabaseHas('sanitary_registries', [
        'registration_number' => 'INVIMA 2026M-9876543', // Saved as uppercase
        'laboratory_id' => $lab->id,
        'expiration_date' => $futureDate,
        'status' => 'valid',
        'description' => 'Notas del registro.',
        'created_by' => $user->id,
    ]);

    expect(session('success'))->toBe('El registro sanitario ha sido registrado con éxito.');
});

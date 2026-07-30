<?php

declare(strict_types=1);

use App\Models\Laboratory;
use App\Models\User;
use Livewire\Volt\Volt;

test('guest users are redirected to login page from create', function () {
    $response = $this->get(route('laboratories.create'));

    $response->assertRedirect('/login');
});

test('authorized users can access laboratory creation page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get(route('laboratories.create'));

    $response
        ->assertOk()
        ->assertSeeVolt('laboratories.create')
        ->assertSee(route('laboratories.index'));
});

test('it validates name is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('laboratories.create')
        ->set('name', '')
        ->call('save');

    $component
        ->assertHasErrors(['name' => 'required'])
        ->assertSee('El nombre del laboratorio es obligatorio.');
});

test('it validates name is maximum 255 characters', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('laboratories.create')
        ->set('name', str_repeat('a', 256))
        ->call('save');

    $component
        ->assertHasErrors(['name' => 'max'])
        ->assertSee('El nombre del laboratorio no debe exceder los 255 caracteres.');
});

test('it validates name is unique in database', function () {
    $user = User::factory()->create();
    Laboratory::factory()->create(['name' => 'Pfizer']);

    $this->actingAs($user);

    $component = Volt::test('laboratories.create')
        ->set('name', 'Pfizer')
        ->call('save');

    $component
        ->assertHasErrors(['name' => 'unique'])
        ->assertSee('Este laboratorio ya se encuentra registrado.');
});

test('it validates description does not exceed 255 characters', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('laboratories.create')
        ->set('name', 'Genfar')
        ->set('description', str_repeat('a', 256))
        ->call('save');

    $component
        ->assertHasErrors(['description' => 'max'])
        ->assertSee('La descripción no debe exceder los 255 caracteres.');
});

test('it successfully creates a laboratory with valid data and resets form', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('laboratories.create')
        ->set('name', 'Pfizer')
        ->set('description', 'Notas del laboratorio Pfizer.')
        ->call('save');

    $component
        ->assertHasNoErrors()
        ->assertSee('El laboratorio ha sido registrado con éxito.');

    $this->assertDatabaseHas('laboratories', [
        'name' => 'Pfizer',
        'description' => 'Notas del laboratorio Pfizer.',
        'created_by' => $user->id,
    ]);

    expect($component->get('name'))->toBe('')
        ->and($component->get('description'))->toBe('');
});

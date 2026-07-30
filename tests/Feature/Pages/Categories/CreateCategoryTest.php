<?php

use App\Models\Category;
use App\Models\User;
use Livewire\Volt\Volt;

test('guest users are redirected to login page', function () {
    $response = $this->get(route('categories.create'));

    $response->assertRedirect('/login');
});

test('authorized users can access category creation page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get(route('categories.create'));

    $response
        ->assertOk()
        ->assertSeeVolt('categories.create')
        ->assertSee(route('categories.index'));
});

test('it validates name is required', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('categories.create')
        ->set('name', '')
        ->call('save');

    $component
        ->assertHasErrors(['name' => 'required'])
        ->assertSee('El nombre de la categoría es obligatorio.');
});

test('it validates name is at least 3 characters', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('categories.create')
        ->set('name', 'ab')
        ->call('save');

    $component
        ->assertHasErrors(['name' => 'min'])
        ->assertSee('El nombre de la categoría debe tener al menos 3 caracteres.');
});

test('it validates name is maximum 50 characters', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('categories.create')
        ->set('name', str_repeat('a', 51))
        ->call('save');

    $component
        ->assertHasErrors(['name' => 'max'])
        ->assertSee('El nombre de la categoría no debe exceder los 50 caracteres.');
});

test('it validates name is unique in database', function () {
    $user = User::factory()->create();
    $existingCategory = Category::factory()->create(['name' => 'Analgésicos']);

    $this->actingAs($user);

    $component = Volt::test('categories.create')
        ->set('name', 'Analgésicos')
        ->call('save');

    $component
        ->assertHasErrors(['name' => 'unique'])
        ->assertSee('El nombre de la categoría ya se encuentra registrado.');
});

test('it validates description does not exceed 255 characters', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('categories.create')
        ->set('name', 'Antibióticos')
        ->set('description', str_repeat('a', 256))
        ->call('save');

    $component
        ->assertHasErrors(['description' => 'max'])
        ->assertSee('La descripción no debe exceder los 255 caracteres.');
});

test('it successfully creates a category with valid data and resets form', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $component = Volt::test('categories.create')
        ->set('name', 'Antibióticos')
        ->set('description', 'Categoría de antibióticos generales.')
        ->set('is_cold_chain', true)
        ->set('is_special_control', true)
        ->call('save');

    $component
        ->assertHasNoErrors()
        ->assertSee('La categoría ha sido registrada con éxito.');

    $this->assertDatabaseHas('categories', [
        'name' => 'Antibióticos',
        'description' => 'Categoría de antibióticos generales.',
        'is_cold_chain' => true,
        'is_special_control' => true,
        'created_by' => $user->id,
    ]);

    expect($component->get('name'))->toBe('')
        ->and($component->get('description'))->toBe('')
        ->and($component->get('is_cold_chain'))->toBeFalse()
        ->and($component->get('is_special_control'))->toBeFalse();
});

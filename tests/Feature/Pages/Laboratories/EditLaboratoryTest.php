<?php

declare(strict_types=1);

use App\Models\Laboratory;
use App\Models\User;
use Livewire\Volt\Volt;

test('guest users are redirected to login page from edit', function () {
    $laboratory = Laboratory::factory()->create();
    $response = $this->get(route('laboratories.edit', $laboratory));

    $response->assertRedirect('/login');
});

test('authorized users can access laboratory edit page', function () {
    $user = User::factory()->create();
    $laboratory = Laboratory::factory()->create();

    $this->actingAs($user);

    $response = $this->get(route('laboratories.edit', $laboratory));

    $response
        ->assertOk()
        ->assertSeeVolt('laboratories.edit');
});

test('it loads the laboratory data into the form', function () {
    $user = User::factory()->create();
    $laboratory = Laboratory::factory()->create([
        'name' => 'Laboratorio Test',
        'description' => 'Descripción de prueba.',
    ]);

    $this->actingAs($user);

    $component = Volt::test('laboratories.edit', ['laboratory' => $laboratory]);

    expect($component->get('name'))->toBe('Laboratorio Test')
        ->and($component->get('description'))->toBe('Descripción de prueba.');
});

test('it validates name is required on edit', function () {
    $user = User::factory()->create();
    $laboratory = Laboratory::factory()->create(['name' => 'Pfizer']);

    $this->actingAs($user);

    $component = Volt::test('laboratories.edit', ['laboratory' => $laboratory])
        ->set('name', '')
        ->call('save');

    $component
        ->assertHasErrors(['name' => 'required'])
        ->assertSee('El nombre del laboratorio es obligatorio.');
});

test('it validates name is maximum 255 characters on edit', function () {
    $user = User::factory()->create();
    $laboratory = Laboratory::factory()->create(['name' => 'Pfizer']);

    $this->actingAs($user);

    $component = Volt::test('laboratories.edit', ['laboratory' => $laboratory])
        ->set('name', str_repeat('a', 256))
        ->call('save');

    $component
        ->assertHasErrors(['name' => 'max'])
        ->assertSee('El nombre del laboratorio no debe exceder los 255 caracteres.');
});

test('it validates description does not exceed 255 characters on edit', function () {
    $user = User::factory()->create();
    $laboratory = Laboratory::factory()->create(['name' => 'Pfizer']);

    $this->actingAs($user);

    $component = Volt::test('laboratories.edit', ['laboratory' => $laboratory])
        ->set('description', str_repeat('a', 256))
        ->call('save');

    $component
        ->assertHasErrors(['description' => 'max'])
        ->assertSee('La descripción no debe exceder los 255 caracteres.');
});

test('it validates name uniqueness against other laboratories except itself', function () {
    $user = User::factory()->create();
    $laboratory1 = Laboratory::factory()->create(['name' => 'Pfizer']);
    $laboratory2 = Laboratory::factory()->create(['name' => 'Genfar']);

    $softDeletedLab = Laboratory::factory()->create(['name' => 'Bayer']);
    $softDeletedLab->delete(); // Soft delete it

    $this->actingAs($user);

    // 1. Keeping its own name should pass
    $component = Volt::test('laboratories.edit', ['laboratory' => $laboratory1])
        ->call('save');

    $component->assertHasNoErrors();

    // 2. Using another active laboratory name should fail
    $component = Volt::test('laboratories.edit', ['laboratory' => $laboratory1])
        ->set('name', 'Genfar')
        ->call('save');

    $component
        ->assertHasErrors(['name' => 'unique'])
        ->assertSee('Este laboratorio ya se encuentra registrado.');

    // 3. Using a soft-deleted laboratory name should fail
    $component = Volt::test('laboratories.edit', ['laboratory' => $laboratory1])
        ->set('name', 'Bayer')
        ->call('save');

    $component
        ->assertHasErrors(['name' => 'unique'])
        ->assertSee('Este laboratorio ya se encuentra registrado.');
});

test('it successfully updates the laboratory, registers modifier details, and redirects to index', function () {
    $user = User::factory()->create();
    $originalCreator = User::factory()->create();

    $laboratory = Laboratory::factory()->create([
        'name' => 'Original',
        'description' => 'Original description',
        'created_by' => $originalCreator->id,
    ]);

    $this->actingAs($user);

    $component = Volt::test('laboratories.edit', ['laboratory' => $laboratory])
        ->set('name', 'Actualizado')
        ->set('description', 'Nueva descripción')
        ->call('save');

    $component
        ->assertHasNoErrors()
        ->assertRedirect(route('laboratories.index'));

    $this->assertDatabaseHas('laboratories', [
        'id' => $laboratory->id,
        'name' => 'Actualizado',
        'description' => 'Nueva descripción',
        'created_by' => $originalCreator->id, // Unchanged
        'updated_by' => $user->id, // Registered current user
    ]);

    // Check flash message exists in session
    expect(session('success'))->toBe('El laboratorio ha sido actualizado con éxito.');
});

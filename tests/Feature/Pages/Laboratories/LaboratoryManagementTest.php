<?php

declare(strict_types=1);

use App\Models\Laboratory;
use App\Models\Medicine;
use App\Models\User;
use Livewire\Volt\Volt;

test('guest users are redirected to login page from index', function () {
    $response = $this->get(route('laboratories.index'));

    $response->assertRedirect('/login');
});

test('authorized users can access laboratory management index page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get(route('laboratories.index'));

    $response
        ->assertOk()
        ->assertSeeVolt('laboratories.index');
});

test('it displays the list of active laboratories', function () {
    $user = User::factory()->create();
    Laboratory::factory()->create(['name' => 'Pfizer']);
    Laboratory::factory()->create(['name' => 'Genfar']);

    $this->actingAs($user);

    $component = Volt::test('laboratories.index');

    $component
        ->assertSee('Pfizer')
        ->assertSee('Genfar');
});

test('it can soft delete a laboratory and record deleter', function () {
    $user = User::factory()->create();
    $laboratory = Laboratory::factory()->create(['name' => 'Laboratorio a Eliminar']);

    $this->actingAs($user);

    $component = Volt::test('laboratories.index')
        ->call('confirmLaboratoryDeletion', $laboratory->id)
        ->call('deleteLaboratory');

    $component
        ->assertHasNoErrors()
        ->assertSee('El laboratorio ha sido eliminado con éxito.');

    $this->assertSoftDeleted('laboratories', [
        'id' => $laboratory->id,
        'deleted_by' => $user->id,
    ]);
});

test('it cannot delete a laboratory if it has active medicines', function () {
    $user = User::factory()->create();
    $laboratory = Laboratory::factory()->create(['name' => 'Laboratorio con Medicamentos']);

    // Create an active medicine associated with this laboratory
    Medicine::factory()->create([
        'laboratory_id' => $laboratory->id,
    ]);

    $this->actingAs($user);

    $component = Volt::test('laboratories.index')
        ->call('confirmLaboratoryDeletion', $laboratory->id)
        ->call('deleteLaboratory');

    $component
        ->assertHasErrors(['deletion_error'])
        ->assertSee('No se puede eliminar el laboratorio porque tiene medicamentos activos asociados.');

    // Verify it was NOT soft-deleted
    $this->assertDatabaseHas('laboratories', [
        'id' => $laboratory->id,
        'deleted_at' => null,
    ]);
});

test('it can search laboratories by name', function () {
    $user = User::factory()->create();
    Laboratory::factory()->create(['name' => 'Pfizer']);
    Laboratory::factory()->create(['name' => 'Genfar']);

    $this->actingAs($user);

    // Search for "Pfiz"
    $component = Volt::test('laboratories.index', ['search' => 'Pfiz']);
    $component
        ->assertSee('Pfizer')
        ->assertDontSee('Genfar');
});

test('it can filter laboratories by status', function () {
    $user = User::factory()->create();
    $activeLab = Laboratory::factory()->create(['name' => 'Laboratorio Activo']);
    $archivedLab = Laboratory::factory()->create(['name' => 'Laboratorio Archivado']);
    $archivedLab->delete(); // Soft delete it

    $this->actingAs($user);

    // Default status is 'active'
    $component = Volt::test('laboratories.index');
    $component
        ->assertSee('Laboratorio Activo')
        ->assertDontSee('Laboratorio Archivado');

    // Filter by 'archived'
    $component = Volt::test('laboratories.index', ['status' => 'archived']);
    $component
        ->assertSee('Laboratorio Archivado')
        ->assertDontSee('Laboratorio Activo');

    // Filter by 'all'
    $component = Volt::test('laboratories.index', ['status' => 'all']);
    $component
        ->assertSee('Laboratorio Activo')
        ->assertSee('Laboratorio Archivado');
});

test('it can restore a soft-deleted laboratory', function () {
    $user = User::factory()->create();
    $laboratory = Laboratory::factory()->create(['name' => 'Laboratorio Archivado']);
    $laboratory->delete(); // Soft delete it

    $this->actingAs($user);

    $component = Volt::test('laboratories.index', ['status' => 'archived'])
        ->call('restoreLaboratory', $laboratory->id);

    $component
        ->assertHasNoErrors()
        ->assertSee('El laboratorio ha sido restaurado con éxito.');

    $this->assertDatabaseHas('laboratories', [
        'id' => $laboratory->id,
        'deleted_at' => null,
        'deleted_by' => null,
    ]);
});

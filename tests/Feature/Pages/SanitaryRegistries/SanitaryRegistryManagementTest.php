<?php

declare(strict_types=1);

use App\Models\Medicine;
use App\Models\SanitaryRegistry;
use App\Models\User;
use Livewire\Volt\Volt;

test('guest users are redirected to login page from index', function () {
    $response = $this->get(route('sanitary-registries.index'));

    $response->assertRedirect('/login');
});

test('authorized users can access sanitary registries management index page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get(route('sanitary-registries.index'));

    $response
        ->assertOk()
        ->assertSeeVolt('sanitary-registries.index');
});

test('it displays the list of active sanitary registries', function () {
    $user = User::factory()->create();
    SanitaryRegistry::factory()->create(['registration_number' => 'INVIMA 2026M-1111111']);
    SanitaryRegistry::factory()->create(['registration_number' => 'INVIMA 2026M-2222222']);

    $this->actingAs($user);

    $component = Volt::test('sanitary-registries.index');

    $component
        ->assertSee('INVIMA 2026M-1111111')
        ->assertSee('INVIMA 2026M-2222222');
});

test('it can soft delete a sanitary registry and record deleter', function () {
    $user = User::factory()->create();
    $registry = SanitaryRegistry::factory()->create(['registration_number' => 'INVIMA 2026M-3333333']);

    $this->actingAs($user);

    $component = Volt::test('sanitary-registries.index')
        ->call('confirmRegistryDeletion', $registry->id)
        ->call('deleteRegistry');

    $component
        ->assertHasNoErrors()
        ->assertSee('El registro sanitario ha sido eliminado con éxito.');

    $this->assertSoftDeleted('sanitary_registries', [
        'id' => $registry->id,
        'deleted_by' => $user->id,
    ]);
});

test('it cannot delete a sanitary registry if it has active medicines', function () {
    $user = User::factory()->create();

    // Create registry and associated active medicine
    $registry = SanitaryRegistry::factory()->create(['registration_number' => 'INVIMA 2026M-4444444']);
    Medicine::factory()->create([
        'sanitary_registry_id' => $registry->id,
    ]);

    $this->actingAs($user);

    $component = Volt::test('sanitary-registries.index')
        ->call('confirmRegistryDeletion', $registry->id)
        ->call('deleteRegistry');

    $component
        ->assertHasErrors(['deletion_error'])
        ->assertSee('No se puede eliminar el registro sanitario porque tiene medicamentos activos asociados.');

    // Verify it was NOT soft-deleted
    $this->assertDatabaseHas('sanitary_registries', [
        'id' => $registry->id,
        'deleted_at' => null,
    ]);
});

test('it can search sanitary registries by number', function () {
    $user = User::factory()->create();
    SanitaryRegistry::factory()->create(['registration_number' => 'INVIMA 2026M-1111111']);
    SanitaryRegistry::factory()->create(['registration_number' => 'INVIMA 2026M-2222222']);

    $this->actingAs($user);

    // Search for "1111"
    $component = Volt::test('sanitary-registries.index', ['search' => '1111']);
    $component
        ->assertSee('INVIMA 2026M-1111111')
        ->assertDontSee('INVIMA 2026M-2222222');
});

test('it can filter sanitary registries by status and soft delete state', function () {
    $user = User::factory()->create();

    $validRegistry = SanitaryRegistry::factory()->create(['registration_number' => 'INVIMA 2026M-1111111', 'status' => 'valid']);
    $expiredRegistry = SanitaryRegistry::factory()->create(['registration_number' => 'INVIMA 2026M-2222222', 'status' => 'expired']);
    $renewalRegistry = SanitaryRegistry::factory()->create(['registration_number' => 'INVIMA 2026M-3333333', 'status' => 'under_renewal']);

    $archivedRegistry = SanitaryRegistry::factory()->create(['registration_number' => 'INVIMA 2026M-4444444']);
    $archivedRegistry->delete(); // soft delete it

    $this->actingAs($user);

    // 1. Default (softDeleteFilter: 'active', statusFilter: 'all')
    $component = Volt::test('sanitary-registries.index');
    $component
        ->assertSee('INVIMA 2026M-1111111')
        ->assertSee('INVIMA 2026M-2222222')
        ->assertSee('INVIMA 2026M-3333333')
        ->assertDontSee('INVIMA 2026M-4444444');

    // 2. Filter by status 'expired'
    $component = Volt::test('sanitary-registries.index', ['statusFilter' => 'expired']);
    $component
        ->assertDontSee('INVIMA 2026M-1111111')
        ->assertSee('INVIMA 2026M-2222222')
        ->assertDontSee('INVIMA 2026M-3333333');

    // 3. Filter by softDeleteFilter 'archived'
    $component = Volt::test('sanitary-registries.index', ['softDeleteFilter' => 'archived']);
    $component
        ->assertDontSee('INVIMA 2026M-1111111')
        ->assertSee('INVIMA 2026M-4444444');
});

test('it can restore a soft-deleted sanitary registry', function () {
    $user = User::factory()->create();
    $registry = SanitaryRegistry::factory()->create(['registration_number' => 'INVIMA 2026M-5555555']);
    $registry->delete(); // soft delete

    $this->actingAs($user);

    $component = Volt::test('sanitary-registries.index', ['softDeleteFilter' => 'archived'])
        ->call('restoreRegistry', $registry->id);

    $component
        ->assertHasNoErrors()
        ->assertSee('El registro sanitario ha sido restaurado con éxito.');

    $this->assertDatabaseHas('sanitary_registries', [
        'id' => $registry->id,
        'deleted_at' => null,
        'deleted_by' => null,
    ]);
});

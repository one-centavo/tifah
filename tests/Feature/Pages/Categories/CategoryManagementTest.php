<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\User;
use Livewire\Volt\Volt;

test('guest users are redirected to login page from index', function () {
    $response = $this->get(route('categories.index'));

    $response->assertRedirect('/login');
});

test('authorized users can access category management index page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get(route('categories.index'));

    $response
        ->assertOk()
        ->assertSeeVolt('categories.index');
});

test('it displays the list of active categories', function () {
    $user = User::factory()->create();
    Category::factory()->create(['name' => 'Antibióticos']);
    Category::factory()->create(['name' => 'Analgésicos']);

    $this->actingAs($user);

    $component = Volt::test('categories.index');

    $component
        ->assertSee('Antibióticos')
        ->assertSee('Analgésicos');
});

test('it can soft delete a category and record deleter', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['name' => 'Categoría a Eliminar']);

    $this->actingAs($user);

    $component = Volt::test('categories.index')
        ->call('deleteCategory', $category->id);

    $component
        ->assertHasNoErrors()
        ->assertSee('La categoría ha sido eliminada con éxito.');

    $this->assertSoftDeleted('categories', [
        'id' => $category->id,
        'deleted_by' => $user->id,
    ]);
});

test('it can search categories by name', function () {
    $user = User::factory()->create();
    Category::factory()->create(['name' => 'Antibióticos']);
    Category::factory()->create(['name' => 'Analgésicos']);

    $this->actingAs($user);

    // Search for "Anti"
    $component = Volt::test('categories.index', ['search' => 'Anti']);
    $component
        ->assertSee('Antibióticos')
        ->assertDontSee('Analgésicos');

    // Search for "anal"
    $component = Volt::test('categories.index', ['search' => 'anal']);
    $component
        ->assertSee('Analgésicos')
        ->assertDontSee('Antibióticos');
});

test('it can filter categories by cold chain status', function () {
    $user = User::factory()->create();
    Category::factory()->create(['name' => 'Cold Chain Yes', 'is_cold_chain' => true]);
    Category::factory()->create(['name' => 'Cold Chain No', 'is_cold_chain' => false]);

    $this->actingAs($user);

    // Filter by "yes"
    $component = Volt::test('categories.index', ['coldChain' => 'yes']);
    $component
        ->assertSee('Cold Chain Yes')
        ->assertDontSee('Cold Chain No');

    // Filter by "no"
    $component = Volt::test('categories.index', ['coldChain' => 'no']);
    $component
        ->assertSee('Cold Chain No')
        ->assertDontSee('Cold Chain Yes');
});

test('it can filter categories by special control status', function () {
    $user = User::factory()->create();
    Category::factory()->create(['name' => 'Special Yes', 'is_special_control' => true]);
    Category::factory()->create(['name' => 'Special No', 'is_special_control' => false]);

    $this->actingAs($user);

    // Filter by "yes"
    $component = Volt::test('categories.index', ['specialControl' => 'yes']);
    $component
        ->assertSee('Special Yes')
        ->assertDontSee('Special No');

    // Filter by "no"
    $component = Volt::test('categories.index', ['specialControl' => 'no']);
    $component
        ->assertSee('Special No')
        ->assertDontSee('Special Yes');
});

test('it can filter categories by active/archived status', function () {
    $user = User::factory()->create();
    $active = Category::factory()->create(['name' => 'Categoría Activa']);
    $archived = Category::factory()->create(['name' => 'Categoría Archivada']);
    $archived->delete(); // Soft delete it

    $this->actingAs($user);

    // Default status is 'active', should only see active
    $component = Volt::test('categories.index');
    $component
        ->assertSee('Categoría Activa')
        ->assertDontSee('Categoría Archivada');

    // Filter by 'archived', should only see archived
    $component = Volt::test('categories.index', ['status' => 'archived']);
    $component
        ->assertSee('Categoría Archivada')
        ->assertDontSee('Categoría Activa');

    // Filter by 'all', should see both
    $component = Volt::test('categories.index', ['status' => 'all']);
    $component
        ->assertSee('Categoría Activa')
        ->assertSee('Categoría Archivada');
});

test('it can restore a soft-deleted category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['name' => 'Categoría a Restaurar']);
    $category->update(['deleted_by' => $user->id]);
    $category->delete(); // Soft delete

    $this->actingAs($user);

    $component = Volt::test('categories.index', ['status' => 'archived'])
        ->call('restoreCategory', $category->id);

    $component
        ->assertHasNoErrors()
        ->assertSee('La categoría ha sido restaurada con éxito.');

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'deleted_at' => null,
        'deleted_by' => null,
    ]);
});

test('it displays empty state message when no categories match the filters', function () {
    $user = User::factory()->create();
    Category::factory()->create(['name' => 'Antibióticos']);

    $this->actingAs($user);

    $component = Volt::test('categories.index', ['search' => 'Inexistente']);
    $component
        ->assertSee('No se encontraron categorías que coincidan con los filtros aplicados')
        ->assertDontSee('Antibióticos');
});

test('it sorts categories by name and by status', function () {
    $user = User::factory()->create();
    Category::factory()->create(['name' => 'Beta']);
    Category::factory()->create(['name' => 'Alpha']);

    $this->actingAs($user);

    // Sort by name ascending (default)
    $component = Volt::test('categories.index');
    $component->assertSeeInOrder(['Alpha', 'Beta']);

    // Sort by name descending
    $component->call('sortBy', 'name'); // Toggles to desc
    $component->assertSeeInOrder(['Beta', 'Alpha']);

    // Let's test sorting by status
    $active = Category::factory()->create(['name' => 'Active Category']);
    $archived = Category::factory()->create(['name' => 'Archived Category']);
    $archived->delete();

    // Set status filter to 'all' so we see both, and sort status asc
    $component = Volt::test('categories.index', [
        'status' => 'all',
        'sortField' => 'status',
        'sortDirection' => 'asc',
    ]);
    // Active first, then Archived
    $component->assertSeeInOrder(['Active Category', 'Archived Category']);

    $component->call('sortBy', 'status'); // Toggles to desc
    // Archived first, then Active
    $component->assertSeeInOrder(['Archived Category', 'Active Category']);
});

<?php

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

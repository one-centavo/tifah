<?php

use App\Models\Category;
use App\Models\Medicine;
use App\Models\User;
use Livewire\Volt\Volt;

test('guest users are redirected to login page from edit', function () {
    $category = Category::factory()->create();
    $response = $this->get(route('categories.edit', $category));

    $response->assertRedirect('/login');
});

test('authorized users can access category edit page', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($user);

    $response = $this->get(route('categories.edit', $category));

    $response
        ->assertOk()
        ->assertSeeVolt('categories.edit');
});

test('it loads the category data into the form', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create([
        'name' => 'Categoría Test',
        'description' => 'Descripción de la categoría test.',
        'is_cold_chain' => true,
        'is_special_control' => true,
    ]);

    $this->actingAs($user);

    $component = Volt::test('categories.edit', ['category' => $category]);

    expect($component->get('name'))->toBe('Categoría Test')
        ->and($component->get('description'))->toBe('Descripción de la categoría test.')
        ->and($component->get('is_cold_chain'))->toBeTrue()
        ->and($component->get('is_special_control'))->toBeTrue();
});

test('it validates name is required on edit', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['name' => 'Analgésicos']);

    $this->actingAs($user);

    $component = Volt::test('categories.edit', ['category' => $category])
        ->set('name', '')
        ->call('save');

    $component
        ->assertHasErrors(['name' => 'required'])
        ->assertSee('El nombre de la categoría es obligatorio.');
});

test('it validates name length constraint on edit', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['name' => 'Analgésicos']);

    $this->actingAs($user);

    // Min 3
    $component = Volt::test('categories.edit', ['category' => $category])
        ->set('name', 'ab')
        ->call('save');

    $component
        ->assertHasErrors(['name' => 'min'])
        ->assertSee('El nombre de la categoría debe tener al menos 3 caracteres.');

    // Max 50
    $component = Volt::test('categories.edit', ['category' => $category])
        ->set('name', str_repeat('a', 51))
        ->call('save');

    $component
        ->assertHasErrors(['name' => 'max'])
        ->assertSee('El nombre de la categoría no debe exceder los 50 caracteres.');
});

test('it validates name uniqueness against other categories except itself', function () {
    $user = User::factory()->create();
    $category1 = Category::factory()->create(['name' => 'Analgésicos']);
    $category2 = Category::factory()->create(['name' => 'Antibióticos']);

    $this->actingAs($user);

    // Keep own name - should pass
    $component = Volt::test('categories.edit', ['category' => $category1])
        ->call('save');

    $component->assertHasNoErrors();

    // Use other category name - should fail
    $component = Volt::test('categories.edit', ['category' => $category1])
        ->set('name', 'Antibióticos')
        ->call('save');

    $component
        ->assertHasErrors(['name' => 'unique'])
        ->assertSee('El nombre de la categoría ya se encuentra registrado.');
});

test('it successfully updates the category and registers modifier details', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create([
        'name' => 'Original',
        'description' => 'Original description',
        'is_cold_chain' => false,
        'is_special_control' => false,
    ]);

    $this->actingAs($user);

    $component = Volt::test('categories.edit', ['category' => $category])
        ->set('name', 'Actualizada')
        ->set('description', 'Nueva descripción')
        ->set('is_cold_chain', true)
        ->set('is_special_control', true)
        ->call('save');

    $component
        ->assertHasNoErrors()
        ->assertSee('La categoría ha sido actualizada con éxito.');

    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Actualizada',
        'description' => 'Nueva descripción',
        'is_cold_chain' => true,
        'is_special_control' => true,
        'created_by' => $category->created_by, // unchanged
        'updated_by' => $user->id, // updated
    ]);
});

test('it displays the list of affected medicines belonging to this category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();

    // Create medicines associated to this category
    Medicine::factory()->create([
        'category_id' => $category->id,
        'name' => 'Aspirina 100mg',
        'generic_name' => 'Ácido Acetilsalicílico',
    ]);

    Medicine::factory()->create([
        'category_id' => $category->id,
        'name' => 'Ibuprofeno 400mg',
        'generic_name' => 'Ibuprofeno',
    ]);

    $this->actingAs($user);

    $component = Volt::test('categories.edit', ['category' => $category]);

    $component
        ->assertSee('Aspirina 100mg')
        ->assertSee('Ácido Acetilsalicílico')
        ->assertSee('Ibuprofeno 400mg')
        ->assertSee('Ibuprofeno');
});

<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\ConcentrationUnit;
use App\Models\Container;
use App\Models\ContentUnit;
use App\Models\Laboratory;
use App\Models\Medicine;
use App\Models\MedicineBarcode;
use App\Models\SanitaryRegistry;
use App\Models\User;
use Livewire\Volt\Volt;

test('guest users are redirected to login page from create', function () {
    $response = $this->get(route('medicines.create'));

    $response->assertRedirect('/login');
});

test('authorized users can access medicines creation page', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $response = $this->get(route('medicines.create'));

    $response
        ->assertOk()
        ->assertSeeVolt('medicines.create')
        ->assertSee(route('medicines.index'));
});

test('it validates barcode manual input constraints', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // 1. Must be numeric
    Volt::test('medicines.create')
        ->set('barcode', 'abc123456')
        ->call('save')
        ->assertHasErrors(['barcode' => 'regex'])
        ->assertSee('El código de barras debe estar compuesto únicamente por números.');

    // 2. Too short (less than 8)
    Volt::test('medicines.create')
        ->set('barcode', '12345')
        ->call('save')
        ->assertHasErrors(['barcode' => 'between'])
        ->assertSee('El código de barras debe tener entre 8 y 14 dígitos.');

    // 3. Too long (more than 14)
    Volt::test('medicines.create')
        ->set('barcode', '1234567890123456')
        ->call('save')
        ->assertHasErrors(['barcode' => 'between'])
        ->assertSee('El código de barras debe tener entre 8 y 14 dígitos.');

    // 4. Must be unique
    $existingBarcode = MedicineBarcode::factory()->create(['barcode' => '1234567890']);
    Volt::test('medicines.create')
        ->set('barcode', '1234567890')
        ->call('save')
        ->assertHasErrors(['barcode' => 'unique'])
        ->assertSee('Este código de barras ya se encuentra registrado.');
});

test('it validates commercial name constraints', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // 1. Required
    Volt::test('medicines.create')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required'])
        ->assertSee('El nombre comercial es obligatorio.');

    // 2. Max 100 characters
    Volt::test('medicines.create')
        ->set('name', str_repeat('a', 101))
        ->call('save')
        ->assertHasErrors(['name' => 'max'])
        ->assertSee('El nombre comercial no debe exceder los 100 caracteres.');
});

test('it validates content quantity constraints', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // 1. Required
    Volt::test('medicines.create')
        ->set('content_quantity', null)
        ->call('save')
        ->assertHasErrors(['content_quantity' => 'required'])
        ->assertSee('La cantidad de contenido es obligatoria.');

    // 2. Must be positive / greater than zero
    Volt::test('medicines.create')
        ->set('content_quantity', 0)
        ->call('save')
        ->assertHasErrors(['content_quantity' => 'min'])
        ->assertSee('La cantidad de contenido debe ser mayor a cero.');
});

test('it automatically generates a unique internal barcode when none is provided', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $component = Volt::test('medicines.create');

    // Check initial state is empty
    expect($component->get('barcode'))->toBeEmpty();

    // Trigger generation
    $component->call('generateInternalBarcode');

    $generated = $component->get('barcode');
    expect($generated)->not->toBeEmpty()
        ->and(strlen($generated))->toBe(12)
        ->and(str_starts_with($generated, '999'))->toBeTrue()
        ->and(is_numeric($generated))->toBeTrue();
});

test('it auto-activates cold chain and special control checkboxes based on selected category', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Category with cold chain and special control
    $category = Category::factory()->create([
        'is_cold_chain' => true,
        'is_special_control' => true,
    ]);

    $component = Volt::test('medicines.create')
        ->set('category_id', $category->id);

    expect($component->get('is_cold_chain'))->toBeTrue()
        ->and($component->get('is_special_control'))->toBeTrue();

    // User can override manually
    $component->set('is_cold_chain', false)
        ->set('is_special_control', false);

    expect($component->get('is_cold_chain'))->toBeFalse()
        ->and($component->get('is_special_control'))->toBeFalse();
});

test('it allows searching and selecting a laboratory', function () {
    $user = User::factory()->create();
    $lab = Laboratory::factory()->create(['name' => 'Laboratorio Abbott']);
    $this->actingAs($user);

    $component = Volt::test('medicines.create')
        ->set('laboratorySearch', 'Abbott')
        ->assertSee('Laboratorio Abbott')
        ->call('selectLaboratory', $lab->id, $lab->name);

    expect($component->get('laboratory_id'))->toBe($lab->id)
        ->and($component->get('laboratorySearch'))->toBe('Laboratorio Abbott')
        ->and($component->get('showLaboratoriesDropdown'))->toBeFalse();
});

test('it allows searching and selecting a valid sanitary registry', function () {
    $user = User::factory()->create();
    $reg = SanitaryRegistry::factory()->create([
        'registration_number' => 'INVIMA 2026M-9999999',
        'status' => 'valid',
    ]);
    $this->actingAs($user);

    $component = Volt::test('medicines.create')
        ->set('sanitaryRegistrySearch', '9999999')
        ->assertSee('INVIMA 2026M-9999999')
        ->call('selectSanitaryRegistry', $reg->id, $reg->registration_number);

    expect($component->get('sanitary_registry_id'))->toBe($reg->id)
        ->and($component->get('sanitaryRegistrySearch'))->toBe('INVIMA 2026M-9999999')
        ->and($component->get('showSanitaryRegistriesDropdown'))->toBeFalse();
});

test('it can register a laboratory quickly from the creation form', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $component = Volt::test('medicines.create')
        ->set('laboratorySearch', 'Pfizer')
        ->call('openQuickLabModal');

    expect($component->get('quickLabName'))->toBe('Pfizer');

    $component->set('quickLabDescription', 'Descripción de Pfizer.')
        ->call('saveQuickLaboratory')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('laboratories', [
        'name' => 'Pfizer',
        'description' => 'Descripción de Pfizer.',
    ]);

    $lab = Laboratory::where('name', 'Pfizer')->first();
    expect($component->get('laboratory_id'))->toBe($lab->id)
        ->and($component->get('laboratorySearch'))->toBe('Pfizer');
});

test('it can register a sanitary registry quickly from the creation form', function () {
    $user = User::factory()->create();
    $lab = Laboratory::factory()->create();
    $this->actingAs($user);

    $futureDate = now()->addYear()->format('Y-m-d');

    $component = Volt::test('medicines.create')
        ->set('sanitaryRegistrySearch', 'INVIMA 2026M-8888888')
        ->call('openQuickRegistryModal');

    expect($component->get('quickRegistryNumber'))->toBe('INVIMA 2026M-8888888');

    $component->set('quickRegistryLabId', $lab->id)
        ->set('quickRegistryExpirationDate', $futureDate)
        ->set('quickRegistryStatus', 'valid')
        ->set('quickRegistryDescription', 'Notas del registro.')
        ->call('saveQuickSanitaryRegistry')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('sanitary_registries', [
        'registration_number' => 'INVIMA 2026M-8888888',
        'laboratory_id' => $lab->id,
        'expiration_date' => $futureDate,
        'status' => 'valid',
    ]);

    $reg = SanitaryRegistry::where('registration_number', 'INVIMA 2026M-8888888')->first();
    expect($component->get('sanitary_registry_id'))->toBe($reg->id)
        ->and($component->get('sanitaryRegistrySearch'))->toBe('INVIMA 2026M-8888888');
});

test('it checks for combination uniqueness, blocks save, and opens duplicate warning modal', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Create an existing medicine
    $existingMedicine = Medicine::factory()->create([
        'name' => 'Existing Medicine Name',
        'generic_name' => 'Generic Name Example',
        'concentration_value' => 500,
    ]);

    // Test creating another medicine with the same details
    $component = Volt::test('medicines.create')
        ->set('barcode', '88888888')
        ->set('name', 'Existing Medicine Name')
        ->set('generic_name', 'Generic Name Example')
        ->set('category_id', $existingMedicine->category_id)
        ->set('concentration_value', 500)
        ->set('concentration_unit_id', $existingMedicine->concentration_unit_id)
        ->set('container_id', $existingMedicine->container_id)
        ->set('content_quantity', $existingMedicine->content_quantity)
        ->set('content_unit_id', $existingMedicine->content_unit_id)
        ->set('laboratory_id', $existingMedicine->laboratory_id)
        ->set('sanitary_registry_id', $existingMedicine->sanitary_registry_id)
        ->set('min_stock', 5)
        ->set('selling_price', 1000)
        ->call('save');

    // Duplicate modal state should be populated and open-modal dispatched
    expect($component->get('duplicateMedicineId'))->toBe($existingMedicine->id)
        ->and($component->get('duplicateMedicineName'))->toBe($existingMedicine->name);

    $component->assertDispatched('open-modal', 'duplicate-medicine-modal');

    // Call linkDuplicateBarcode
    $component->call('linkDuplicateBarcode')
        ->assertHasNoErrors()
        ->assertRedirect(route('medicines.index'));

    // The barcode should now be linked to the existing medicine
    $this->assertDatabaseHas('medicine_barcodes', [
        'medicine_id' => $existingMedicine->id,
        'barcode' => '88888888',
        'is_main' => false,
    ]);

    expect(session('success'))->toBe('El código de barras ha sido vinculado al medicamento existente con éxito.');
});

test('it successfully creates a medicine and main barcode inside a transaction, then redirects', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $category = Category::factory()->create();
    $lab = Laboratory::factory()->create();
    $registry = SanitaryRegistry::factory()->create();
    $concentrationUnit = ConcentrationUnit::factory()->create();
    $container = Container::factory()->create();
    $contentUnit = ContentUnit::factory()->create();

    $component = Volt::test('medicines.create')
        ->set('barcode', '12345678')
        ->set('name', 'Acetaminofén MK')
        ->set('generic_name', 'Acetaminofén')
        ->set('category_id', $category->id)
        ->set('concentration_value', 500)
        ->set('concentration_unit_id', $concentrationUnit->id)
        ->set('container_id', $container->id)
        ->set('content_quantity', 30)
        ->set('content_unit_id', $contentUnit->id)
        ->set('laboratory_id', $lab->id)
        ->set('sanitary_registry_id', $registry->id)
        ->set('is_cold_chain', false)
        ->set('is_special_control', false)
        ->set('min_stock', 10)
        ->set('selling_price', 12500.50)
        ->set('description', 'Notas del medicamento.')
        ->call('save');

    $component->assertHasNoErrors()
        ->assertRedirect(route('medicines.index'));

    // Check medicine was saved
    $this->assertDatabaseHas('medicines', [
        'name' => 'Acetaminofén MK',
        'generic_name' => 'Acetaminofén',
        'category_id' => $category->id,
        'concentration_value' => 500.00,
        'concentration_unit_id' => $concentrationUnit->id,
        'container_id' => $container->id,
        'content_quantity' => 30,
        'content_unit_id' => $contentUnit->id,
        'laboratory_id' => $lab->id,
        'sanitary_registry_id' => $registry->id,
        'min_stock' => 10,
        'selling_price' => 12500.50,
        'description' => 'Notas del medicamento.',
        'created_by' => $user->id,
    ]);

    // Check barcode was saved
    $medicine = Medicine::where('name', 'Acetaminofén MK')->first();
    $this->assertDatabaseHas('medicine_barcodes', [
        'medicine_id' => $medicine->id,
        'barcode' => '12345678',
        'is_main' => true,
        'created_by' => $user->id,
    ]);

    expect(session('success'))->toBe('El medicamento ha sido registrado con éxito.');
});

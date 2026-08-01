<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\ConcentrationUnit;
use App\Models\Container;
use App\Models\ContentUnit;
use App\Models\Laboratory;
use App\Models\Lot;
use App\Models\Medicine;
use App\Models\MedicineBarcode;
use App\Models\PurchaseOrder;
use App\Models\SanitaryRegistry;
use App\Models\Supplier;
use App\Models\User;
use Livewire\Volt\Volt;

test('guest users are redirected to login page from edit', function () {
    $medicine = Medicine::factory()->create();
    $response = $this->get(route('medicines.edit', $medicine->id));

    $response->assertRedirect('/login');
});

test('authorized users can access medicines edit page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $medicine = Medicine::factory()->create();

    $response = $this->get(route('medicines.edit', $medicine->id));

    $response
        ->assertOk()
        ->assertSeeVolt('medicines.edit')
        ->assertSee(route('medicines.index'));
});

test('it loads the medicine data into the form', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $medicine = Medicine::factory()->create();
    $barcode = MedicineBarcode::factory()->create([
        'medicine_id' => $medicine->id,
        'barcode' => '7701234567890',
        'is_main' => true,
    ]);

    Volt::test('medicines.edit', ['medicine' => $medicine])
        ->assertSet('name', $medicine->name)
        ->assertSet('generic_name', $medicine->generic_name)
        ->assertSet('category_id', $medicine->category_id)
        ->assertSet('selling_price', (float) $medicine->selling_price)
        ->assertSet('min_stock', $medicine->min_stock)
        ->assertSet('description', $medicine->description)
        ->assertSet('isMasterDataReadOnly', false);
});

test('it validates required and format constraints on edit', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $medicine = Medicine::factory()->create();
    MedicineBarcode::factory()->create([
        'medicine_id' => $medicine->id,
        'barcode' => '7701234567890',
        'is_main' => true,
    ]);

    // 1. Operational validations
    Volt::test('medicines.edit', ['medicine' => $medicine])
        ->set('selling_price', -5)
        ->call('save')
        ->assertHasErrors(['selling_price' => 'min']);

    Volt::test('medicines.edit', ['medicine' => $medicine])
        ->set('min_stock', -1)
        ->call('save')
        ->assertHasErrors(['min_stock' => 'min']);
});

test('it enforces combination uniqueness when master data is editable', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $category = Category::factory()->create();
    $lab = Laboratory::factory()->create();
    $registry = SanitaryRegistry::factory()->create();
    $concentrationUnit = ConcentrationUnit::factory()->create();
    $container = Container::factory()->create();
    $contentUnit = ContentUnit::factory()->create();

    // Medicine A
    $medA = Medicine::create([
        'name' => 'Aspirina MK',
        'generic_name' => 'Aspirina',
        'category_id' => $category->id,
        'concentration_value' => 500,
        'concentration_unit_id' => $concentrationUnit->id,
        'container_id' => $container->id,
        'content_quantity' => 20,
        'content_unit_id' => $contentUnit->id,
        'laboratory_id' => $lab->id,
        'sanitary_registry_id' => $registry->id,
        'min_stock' => 5,
        'selling_price' => 1000,
        'created_by' => $user->id,
    ]);
    MedicineBarcode::factory()->create(['medicine_id' => $medA->id, 'barcode' => '11111111']);

    // Medicine B
    $medB = Medicine::create([
        'name' => 'Advil Max',
        'generic_name' => 'Ibuprofeno',
        'category_id' => $category->id,
        'concentration_value' => 400,
        'concentration_unit_id' => $concentrationUnit->id,
        'container_id' => $container->id,
        'content_quantity' => 10,
        'content_unit_id' => $contentUnit->id,
        'laboratory_id' => $lab->id,
        'sanitary_registry_id' => $registry->id,
        'min_stock' => 5,
        'selling_price' => 2000,
        'created_by' => $user->id,
    ]);
    MedicineBarcode::factory()->create(['medicine_id' => $medB->id, 'barcode' => '22222222']);

    // Edit Medicine B to match Medicine A combination (including concentration)
    Volt::test('medicines.edit', ['medicine' => $medB])
        ->set('name', 'Aspirina MK')
        ->set('generic_name', 'Aspirina')
        ->set('concentration_value', 500)
        ->set('content_quantity', 20)
        ->call('save')
        ->assertHasErrors(['name'])
        ->assertSee('Ya existe un medicamento registrado con esta misma combinación');
});

test('it handles barcode manager operations when master data is editable', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $medicine = Medicine::factory()->create();
    $barcode1 = MedicineBarcode::factory()->create([
        'medicine_id' => $medicine->id,
        'barcode' => '7701234567890',
        'is_main' => true,
    ]);

    // 1. Try to add a malformed barcode
    Volt::test('medicines.edit', ['medicine' => $medicine])
        ->set('newBarcode', 'abc12345')
        ->call('addBarcode')
        ->assertHasErrors(['newBarcode'])
        ->assertSee('El código de barras debe estar compuesto únicamente por números.');

    // 2. Try to add a duplicate barcode in DB
    $otherBarcode = MedicineBarcode::factory()->create(['barcode' => '99999999']);
    Volt::test('medicines.edit', ['medicine' => $medicine])
        ->set('newBarcode', '99999999')
        ->call('addBarcode')
        ->assertHasErrors(['newBarcode'])
        ->assertSee('Este código de barras ya se encuentra registrado.');

    // 3. Add a valid new barcode
    $testComponent = Volt::test('medicines.edit', ['medicine' => $medicine])
        ->set('newBarcode', '88888888')
        ->call('addBarcode')
        ->assertHasNoErrors()
        ->assertSet('newBarcode', '');

    $barcodes = $testComponent->get('barcodes');
    expect($barcodes)->toHaveCount(2)
        ->and($barcodes[1]['barcode'])->toBe('88888888')
        ->and($barcodes[1]['is_new'])->toBeTrue();

    // 4. Remove a barcode (existing barcode)
    $testComponent->call('removeBarcode', 0);
    expect($testComponent->get('barcodes'))->toHaveCount(1)
        ->and($testComponent->get('barcodes')[0]['barcode'])->toBe('88888888');
});

test('it enforces read-only master data when medicine has lots', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $medicine = Medicine::factory()->create();
    $barcode = MedicineBarcode::factory()->create([
        'medicine_id' => $medicine->id,
        'barcode' => '7701234567890',
        'is_main' => true,
    ]);

    // Create a lot for this medicine manually
    $supplier = Supplier::create([
        'nit' => '123456789',
        'dv' => 1,
        'name' => 'Supplier Test',
        'contact_person' => 'John Doe',
        'phone_number' => '1234567',
        'email' => 'supplier@test.com',
        'address' => 'Test address',
        'created_by' => $user->id,
    ]);
    $purchaseOrder = PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'status' => 'pending',
        'expected_date' => now()->addDays(5)->toDateString(),
        'total_estimated' => 100.00,
        'created_by' => $user->id,
    ]);
    $lot = Lot::create([
        'medicine_id' => $medicine->id,
        'purchase_order_id' => $purchaseOrder->id,
        'batch_number' => 'LOT-12345',
        'expiration_date' => now()->addYear()->toDateString(),
        'current_quantity' => 100,
        'initial_quantity' => 100,
        'reception_date' => now()->toDateString(),
        'unit_purchase_price' => 10.50,
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    // Form loads read-only flag
    Volt::test('medicines.edit', ['medicine' => $medicine])
        ->assertSet('isMasterDataReadOnly', true);

    // Attempting to modify master data should be ignored by the service
    $origName = $medicine->name;
    Volt::test('medicines.edit', ['medicine' => $medicine])
        ->set('name', 'NUEVO NOMBRE COMERCIAL')
        ->set('selling_price', 99999.00)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('medicines.index'));

    $medicine->refresh();
    expect($medicine->name)->toBe($origName) // Name should NOT change
        ->and((float) $medicine->selling_price)->toBe(99999.00); // Selling price should change
});

test('it prevents deletion of existing barcodes when master data is read-only', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $medicine = Medicine::factory()->create();
    $barcode = MedicineBarcode::factory()->create([
        'medicine_id' => $medicine->id,
        'barcode' => '7701234567890',
        'is_main' => true,
    ]);

    // Create a lot for this medicine manually
    $supplier = Supplier::create([
        'nit' => '123456789',
        'dv' => 1,
        'name' => 'Supplier Test',
        'contact_person' => 'John Doe',
        'phone_number' => '1234567',
        'email' => 'supplier@test.com',
        'address' => 'Test address',
        'created_by' => $user->id,
    ]);
    $purchaseOrder = PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'status' => 'pending',
        'expected_date' => now()->addDays(5)->toDateString(),
        'total_estimated' => 100.00,
        'created_by' => $user->id,
    ]);
    $lot = Lot::create([
        'medicine_id' => $medicine->id,
        'purchase_order_id' => $purchaseOrder->id,
        'batch_number' => 'LOT-12345',
        'expiration_date' => now()->addYear()->toDateString(),
        'current_quantity' => 100,
        'initial_quantity' => 100,
        'reception_date' => now()->toDateString(),
        'unit_purchase_price' => 10.50,
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    // Attempt to remove the existing barcode in Volt component
    Volt::test('medicines.edit', ['medicine' => $medicine])
        ->call('removeBarcode', 0)
        ->assertHasErrors(['barcodes'])
        ->assertSee('No se pueden eliminar códigos de barras');
});

test('it allows adding new barcodes even when master data is read-only', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $medicine = Medicine::factory()->create();
    $barcode = MedicineBarcode::factory()->create([
        'medicine_id' => $medicine->id,
        'barcode' => '7701234567890',
        'is_main' => true,
    ]);

    // Create a lot manually to lock master data
    $supplier = Supplier::create([
        'nit' => '123456789',
        'dv' => 1,
        'name' => 'Supplier Test',
        'contact_person' => 'John Doe',
        'phone_number' => '1234567',
        'email' => 'supplier@test.com',
        'address' => 'Test address',
        'created_by' => $user->id,
    ]);
    $purchaseOrder = PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'status' => 'pending',
        'expected_date' => now()->addDays(5)->toDateString(),
        'total_estimated' => 100.00,
        'created_by' => $user->id,
    ]);
    Lot::create([
        'medicine_id' => $medicine->id,
        'purchase_order_id' => $purchaseOrder->id,
        'batch_number' => 'LOT-12345',
        'expiration_date' => now()->addYear()->toDateString(),
        'current_quantity' => 100,
        'initial_quantity' => 100,
        'reception_date' => now()->toDateString(),
        'unit_purchase_price' => 10.50,
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    Volt::test('medicines.edit', ['medicine' => $medicine])
        ->set('newBarcode', '88888888')
        ->call('addBarcode')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('medicines.index'));

    $this->assertDatabaseHas('medicine_barcodes', [
        'medicine_id' => $medicine->id,
        'barcode' => '88888888',
        'is_main' => false,
    ]);
});

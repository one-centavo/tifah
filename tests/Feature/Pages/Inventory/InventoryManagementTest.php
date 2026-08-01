<?php

declare(strict_types=1);

use App\Models\Lot;
use App\Models\Medicine;
use App\Models\MedicineBarcode;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Livewire\Volt\Volt;

test('guest users are redirected to login page from index', function () {
    $response = $this->get(route('inventory.index'));

    $response->assertRedirect('/login');
});

test('authorized users can access inventory management page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('inventory.index'));

    $response
        ->assertOk()
        ->assertSeeVolt('inventory.index')
        ->assertSee('Gestión de Inventario');
});

test('it displays the list of active lots', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $supplier = Supplier::create([
        'nit' => '12345678-9',
        'dv' => 9,
        'name' => 'Supplier Test A',
        'contact_person' => 'John Doe',
        'phone_number' => '1234567',
        'email' => 'supplier@test.com',
        'address' => 'Supplier Address',
        'created_by' => $user->id,
    ]);

    $purchaseOrder = PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'status' => 'received',
        'expected_date' => now()->toDateString(),
        'received_at' => now()->toDateString(),
        'total_estimated' => 100.00,
        'created_by' => $user->id,
    ]);

    $medicine = Medicine::factory()->create(['name' => 'Lantus Insulin']);

    Lot::create([
        'medicine_id' => $medicine->id,
        'purchase_order_id' => $purchaseOrder->id,
        'batch_number' => 'BATCHX100',
        'expiration_date' => now()->addYear()->toDateString(),
        'current_quantity' => 50,
        'initial_quantity' => 50,
        'reception_date' => now()->toDateString(),
        'unit_purchase_price' => 10.00,
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    Volt::test('inventory.index')
        ->assertSee('Lantus Insulin')
        ->assertSee('BATCHX100');
});

test('it can search lots by medicine name or batch number', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $supplier = Supplier::create([
        'nit' => '12345678-8',
        'dv' => 8,
        'name' => 'Supplier Test A',
        'contact_person' => 'John Doe',
        'phone_number' => '1234567',
        'email' => 'supplier@test.com',
        'address' => 'Supplier Address',
        'created_by' => $user->id,
    ]);

    $purchaseOrder = PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'status' => 'received',
        'expected_date' => now()->toDateString(),
        'received_at' => now()->toDateString(),
        'total_estimated' => 100.00,
        'created_by' => $user->id,
    ]);

    $medicineA = Medicine::factory()->create(['name' => 'Medicine A']);
    $medicineB = Medicine::factory()->create(['name' => 'Medicine B']);

    Lot::create([
        'medicine_id' => $medicineA->id,
        'purchase_order_id' => $purchaseOrder->id,
        'batch_number' => 'BATCHA',
        'expiration_date' => now()->addYear()->toDateString(),
        'current_quantity' => 10,
        'initial_quantity' => 10,
        'reception_date' => now()->toDateString(),
        'unit_purchase_price' => 5.00,
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    Lot::create([
        'medicine_id' => $medicineB->id,
        'purchase_order_id' => $purchaseOrder->id,
        'batch_number' => 'BATCHB',
        'expiration_date' => now()->addYear()->toDateString(),
        'current_quantity' => 15,
        'initial_quantity' => 15,
        'reception_date' => now()->toDateString(),
        'unit_purchase_price' => 5.00,
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    // Search by medicine name
    Volt::test('inventory.index')
        ->set('search', 'Medicine A')
        ->assertSee('BATCHA')
        ->assertDontSee('BATCHB');

    // Search by batch number
    Volt::test('inventory.index')
        ->set('search', 'BATCHB')
        ->assertSee('BATCHB')
        ->assertDontSee('BATCHA');
});

test('it can soft delete a lot and log audit trail', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $supplier = Supplier::create([
        'nit' => '12345678-7',
        'dv' => 7,
        'name' => 'Supplier Test A',
        'contact_person' => 'John Doe',
        'phone_number' => '1234567',
        'email' => 'supplier@test.com',
        'address' => 'Supplier Address',
        'created_by' => $user->id,
    ]);

    $purchaseOrder = PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'status' => 'received',
        'expected_date' => now()->toDateString(),
        'received_at' => now()->toDateString(),
        'total_estimated' => 100.00,
        'created_by' => $user->id,
    ]);

    $medicine = Medicine::factory()->create(['name' => 'Lantus Insulin']);

    $lot = Lot::create([
        'medicine_id' => $medicine->id,
        'purchase_order_id' => $purchaseOrder->id,
        'batch_number' => 'BATCHX100',
        'expiration_date' => now()->addYear()->toDateString(),
        'current_quantity' => 50,
        'initial_quantity' => 50,
        'reception_date' => now()->toDateString(),
        'unit_purchase_price' => 10.00,
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    Volt::test('inventory.index')
        ->call('confirmLotDeletion', $lot->id)
        ->assertSet('lotIdBeingDeleted', $lot->id)
        ->call('deleteLot');

    $lot->refresh();
    expect($lot->deleted_at)->not->toBeNull();
    expect($lot->deleted_by)->toBe($user->id);
    expect($lot->current_quantity)->toBe(0);

    // Verify inventory movement adjustment log
    $this->assertDatabaseHas('inventory_movements', [
        'lot_id' => $lot->id,
        'type' => 'adjustment',
        'quantity' => -50,
        'new_balance' => 0,
        'created_by' => $user->id,
    ]);
});

test('tab switching preserves reception state', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('inventory.index')
        ->set('activeTab', 'reception')
        ->set('barcode', '12345')
        ->set('activeTab', 'lots')
        ->assertSet('barcode', '12345');
});

test('barcode input resolves matching medicine details', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $medicine = Medicine::factory()->create(['name' => 'Aspirina MK', 'selling_price' => 12.50]);
    MedicineBarcode::factory()->create([
        'medicine_id' => $medicine->id,
        'barcode' => '7709876543210',
        'is_main' => true,
    ]);

    Volt::test('inventory.index')
        ->set('activeTab', 'reception')
        ->set('barcode', '7709876543210')
        ->assertSet('selectedMedicineId', $medicine->id)
        ->assertSet('selectedMedicineName', 'Aspirina MK')
        ->assertSet('selectedMedicineSellingPrice', 12.50);
});

test('expiration date check blocks registration if expired', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $medicine = Medicine::factory()->create(['name' => 'Aspirina MK']);
    $supplier = Supplier::create([
        'nit' => '11111111-1',
        'dv' => 1,
        'name' => 'Supplier X',
        'contact_person' => 'Jane',
        'phone_number' => '123',
        'email' => 'x@x.com',
        'address' => 'Addr',
        'created_by' => $user->id,
    ]);

    Volt::test('inventory.index')
        ->set('activeTab', 'reception')
        ->set('selectedMedicineId', $medicine->id)
        ->set('selectedMedicineName', $medicine->name)
        ->set('batch_number', 'BATCH123')
        ->set('expiration_date', now()->subDay()->toDateString()) // Expired
        ->set('quantity', '10')
        ->set('unit_purchase_price', '5.00')
        ->set('supplier_id', (string) $supplier->id)
        ->call('addToTemporaryList')
        ->assertHasErrors(['expiration_date' => 'No se pueden ingresar productos vencidos.'])
        ->assertCount('temporaryLots', 0);
});

test('merging duplicate batch numbers updates quantities and subtotals', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $medicine = Medicine::factory()->create(['name' => 'Aspirina MK']);
    $supplier = Supplier::create([
        'nit' => '11111111-2',
        'dv' => 2,
        'name' => 'Supplier X',
        'contact_person' => 'Jane',
        'phone_number' => '123',
        'email' => 'x@x.com',
        'address' => 'Addr',
        'created_by' => $user->id,
    ]);

    Volt::test('inventory.index')
        ->set('activeTab', 'reception')
        ->set('selectedMedicineId', $medicine->id)
        ->set('selectedMedicineName', $medicine->name)
        ->set('batch_number', 'BATCHX')
        ->set('expiration_date', now()->addYear()->toDateString())
        ->set('quantity', '10')
        ->set('unit_purchase_price', '5.00')
        ->set('supplier_id', (string) $supplier->id)
        ->call('addToTemporaryList')

        // Add same batch again
        ->set('selectedMedicineId', $medicine->id)
        ->set('selectedMedicineName', $medicine->name)
        ->set('batch_number', 'BATCHX')
        ->set('expiration_date', now()->addYear()->toDateString())
        ->set('quantity', '15')
        ->set('unit_purchase_price', '5.00')
        ->set('supplier_id', (string) $supplier->id)
        ->call('addToTemporaryList')

        ->assertCount('temporaryLots', 1)
        ->assertSet('temporaryLots.0.quantity', 25)
        ->assertSet('temporaryLots.0.total_price', 125.00);
});

test('quick supplier registration modal successfully persists new supplier', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('inventory.index')
        ->set('activeTab', 'reception')
        ->call('openQuickSupplierModal')
        ->set('supplier_nit', '900555666')
        ->set('supplier_dv', '2')
        ->set('supplier_name', 'Quick Supplier SAS')
        ->set('supplier_contact_person', 'Test Contact')
        ->set('supplier_phone_number', '5556667')
        ->set('supplier_email', 'quick@supplier.com')
        ->set('supplier_address', 'St. 100')
        ->call('saveQuickSupplier')
        ->assertSet('showSupplierModal', false);

    $this->assertDatabaseHas('suppliers', [
        'nit' => '900555666',
        'name' => 'Quick Supplier SAS',
    ]);
});

test('confirming reception creates database entries and updates total stock', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $medicine = Medicine::factory()->create(['name' => 'Aspirina MK']);
    $supplier = Supplier::create([
        'nit' => '11111111-3',
        'dv' => 3,
        'name' => 'Supplier X',
        'contact_person' => 'Jane',
        'phone_number' => '123',
        'email' => 'x@x.com',
        'address' => 'Addr',
        'created_by' => $user->id,
    ]);

    Volt::test('inventory.index')
        ->set('activeTab', 'reception')
        ->set('selectedMedicineId', $medicine->id)
        ->set('selectedMedicineName', $medicine->name)
        ->set('batch_number', 'BATCH1')
        ->set('expiration_date', now()->addYear()->toDateString())
        ->set('quantity', '30')
        ->set('unit_purchase_price', '4.50')
        ->set('supplier_id', (string) $supplier->id)
        ->call('addToTemporaryList')

        // Add another medicine
        ->set('selectedMedicineId', $medicine->id)
        ->set('selectedMedicineName', $medicine->name)
        ->set('batch_number', 'BATCH2')
        ->set('expiration_date', now()->addYear()->toDateString())
        ->set('quantity', '20')
        ->set('unit_purchase_price', '5.00')
        ->set('supplier_id', (string) $supplier->id)
        ->call('addToTemporaryList')

        ->call('confirmReception')
        ->assertSet('activeTab', 'lots')
        ->assertCount('temporaryLots', 0);

    // Verify Purchase Order
    $this->assertDatabaseHas('purchase_orders', [
        'supplier_id' => $supplier->id,
        'status' => 'received',
        'total_estimated' => 235.00, // (30*4.5) + (20*5) = 135 + 100 = 235
    ]);

    // Verify Lots
    $this->assertDatabaseHas('lots', [
        'medicine_id' => $medicine->id,
        'batch_number' => 'BATCH1',
        'initial_quantity' => 30,
        'current_quantity' => 30,
    ]);

    $this->assertDatabaseHas('lots', [
        'medicine_id' => $medicine->id,
        'batch_number' => 'BATCH2',
        'initial_quantity' => 20,
        'current_quantity' => 20,
    ]);

    // Verify dynamic stock calculation matches sum of both batches
    $medicine->refresh();
    expect($medicine->total_stock)->toBe(50);
});

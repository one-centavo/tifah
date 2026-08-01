<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Lot;
use App\Models\Medicine;
use App\Models\MedicineBarcode;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Livewire\Volt\Volt;

test('guest users are redirected to login page from index', function () {
    $response = $this->get(route('medicines.index'));

    $response->assertRedirect('/login');
});

test('authorized users can access medicines management index page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('medicines.index'));

    $response
        ->assertOk()
        ->assertSeeVolt('medicines.index')
        ->assertSee('Catálogo de Medicamentos');
});

test('it displays the list of active medicines', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $medicine = Medicine::factory()->create(['name' => 'Lantus Insulin']);
    $barcode = MedicineBarcode::factory()->create([
        'medicine_id' => $medicine->id,
        'barcode' => '7701234567890',
        'is_main' => true,
    ]);

    Volt::test('medicines.index')
        ->assertSee('Lantus Insulin')
        ->assertSee('7701234567890');
});

test('it can search medicines by commercial name, generic name, or barcode', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $med1 = Medicine::factory()->create(['name' => 'Amoxicilina MK', 'generic_name' => 'Amoxicilina']);
    $bar1 = MedicineBarcode::factory()->create(['medicine_id' => $med1->id, 'barcode' => '1111111111']);

    $med2 = Medicine::factory()->create(['name' => 'Ibuprofeno Genfar', 'generic_name' => 'Ibuprofeno']);
    $bar2 = MedicineBarcode::factory()->create(['medicine_id' => $med2->id, 'barcode' => '2222222222']);

    // Search by name
    Volt::test('medicines.index')
        ->set('search', 'Amoxicilina')
        ->assertSee('Amoxicilina MK')
        ->assertDontSee('Ibuprofeno Genfar');

    // Search by barcode
    Volt::test('medicines.index')
        ->set('search', '2222222222')
        ->assertSee('Ibuprofeno Genfar')
        ->assertDontSee('Amoxicilina MK');
});

test('it can filter medicines by category', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $cat1 = Category::factory()->create(['name' => 'Antibióticos']);
    $med1 = Medicine::factory()->create(['name' => 'Amoxicilina MK', 'category_id' => $cat1->id]);

    $cat2 = Category::factory()->create(['name' => 'Analgésicos']);
    $med2 = Medicine::factory()->create(['name' => 'Ibuprofeno Genfar', 'category_id' => $cat2->id]);

    Volt::test('medicines.index')
        ->set('categoryFilter', (string) $cat1->id)
        ->assertSee('Amoxicilina MK')
        ->assertDontSee('Ibuprofeno Genfar');
});

test('it can soft delete a medicine and record deleter and delete related barcodes', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $medicine = Medicine::factory()->create(['name' => 'Rivotril']);
    $barcode = MedicineBarcode::factory()->create([
        'medicine_id' => $medicine->id,
        'barcode' => '3333333333',
        'is_main' => true,
    ]);

    $component = Volt::test('medicines.index')
        ->call('confirmMedicineDeletion', $medicine->id);

    expect($component->get('medicineIdBeingDeleted'))->toBe($medicine->id)
        ->and($component->get('medicineNameBeingDeleted'))->toBe('Rivotril');

    $component->call('deleteMedicine')
        ->assertHasNoErrors();

    // Medicine should be soft deleted and record deleter
    $medicine->refresh();
    expect($medicine->trashed())->toBeTrue()
        ->and($medicine->deleted_by)->toBe($user->id);

    // Barcode should also be soft deleted and record deleter
    $barcode->refresh();
    expect($barcode->trashed())->toBeTrue()
        ->and($barcode->deleted_by)->toBe($user->id);

    // Should no longer see it in active list
    Volt::test('medicines.index')
        ->assertDontSee('Rivotril');

    // Should see it when filtering by archived
    Volt::test('medicines.index')
        ->set('softDeleteFilter', 'archived')
        ->assertSee('Rivotril');
});

test('it displays the total stock and show warning badge if stock is equal or below minimum', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $supplier = Supplier::create([
        'nit' => '12345678-9',
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

    // Medicine 1: Stock is below minimum -> Show alert
    $medicineBelow = Medicine::factory()->create([
        'name' => 'Medicine Below',
        'min_stock' => 10,
    ]);
    Lot::create([
        'medicine_id' => $medicineBelow->id,
        'purchase_order_id' => $purchaseOrder->id,
        'batch_number' => 'LOT001',
        'expiration_date' => now()->addYear(),
        'current_quantity' => 5,
        'initial_quantity' => 10,
        'reception_date' => now(),
        'unit_purchase_price' => 50.0,
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    // Medicine 2: Stock is above minimum -> No alert
    $medicineAbove = Medicine::factory()->create([
        'name' => 'Medicine Above',
        'min_stock' => 10,
    ]);
    Lot::create([
        'medicine_id' => $medicineAbove->id,
        'purchase_order_id' => $purchaseOrder->id,
        'batch_number' => 'LOT002',
        'expiration_date' => now()->addYear(),
        'current_quantity' => 15,
        'initial_quantity' => 20,
        'reception_date' => now(),
        'unit_purchase_price' => 50.0,
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    Volt::test('medicines.index')
        ->assertSee('Medicine Below')
        ->assertSee('Medicine Above')
        ->assertSee('5')
        ->assertSee('15')
        ->assertSee('Alerta');
});

test('it can load technical details inside the detail modal', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $medicine = Medicine::factory()->create([
        'name' => 'Test Detail Medicine',
        'generic_name' => 'Test Generic Active',
        'concentration_value' => 500.00,
        'selling_price' => 15000.00,
        'description' => 'This is a description test for the modal detail.',
    ]);

    Volt::test('medicines.index')
        ->call('viewDetails', $medicine->id)
        ->assertSet('selectedMedicineId', $medicine->id)
        ->assertSee('Test Detail Medicine')
        ->assertSee('Test Generic Active')
        ->assertSee('This is a description test for the modal detail.');
});

test('it prevents soft deletion of a medicine with active lots', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $supplier = Supplier::create([
        'nit' => '12345678-9',
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

    $medicine = Medicine::factory()->create(['name' => 'Medicine With Active Lots']);
    Lot::create([
        'medicine_id' => $medicine->id,
        'purchase_order_id' => $purchaseOrder->id,
        'batch_number' => 'LOT003',
        'expiration_date' => now()->addYear(),
        'current_quantity' => 10,
        'initial_quantity' => 10,
        'reception_date' => now(),
        'unit_purchase_price' => 50.0,
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    Volt::test('medicines.index')
        ->call('confirmMedicineDeletion', $medicine->id)
        ->call('deleteMedicine')
        ->assertHasErrors(['deletion_error'])
        ->assertSee('No se puede archivar el medicamento porque existen lotes activos en el inventario asociados a este producto.');

    // Medicine should not be soft deleted
    $medicine->refresh();
    expect($medicine->trashed())->toBeFalse();
});

test('it displays creator and updater details and actions in the medicine detail modal', function () {
    $creator = User::factory()->create(['first_name' => 'John', 'last_name' => 'Creator']);
    $updater = User::factory()->create(['first_name' => 'Jane', 'last_name' => 'Updater']);
    $this->actingAs($creator);

    $medicine = Medicine::factory()->create([
        'name' => 'Test Detail Audit Medicine',
        'created_by' => $creator->id,
        'updated_by' => $updater->id,
    ]);

    Volt::test('medicines.index')
        ->call('viewDetails', $medicine->id)
        ->assertSee('John Creator')
        ->assertSee('Jane Updater')
        ->assertSee('Editar')
        ->assertSee('Archivar');
});

test('it displays soft deletion audit details and hides actions for archived medicine in the detail modal', function () {
    $creator = User::factory()->create(['first_name' => 'John', 'last_name' => 'Creator']);
    $deleter = User::factory()->create(['first_name' => 'Mark', 'last_name' => 'Deleter']);
    $this->actingAs($creator);

    $medicine = Medicine::factory()->create([
        'name' => 'Archived Medicine Detail',
        'created_by' => $creator->id,
    ]);

    $medicine->deleted_by = $deleter->id;
    $medicine->save();
    $medicine->delete();

    Volt::test('medicines.index')
        ->set('softDeleteFilter', 'archived')
        ->call('viewDetails', $medicine->id)
        ->assertSee('Archived Medicine Detail')
        ->assertSee('Eliminado por:')
        ->assertSee('Mark Deleter')
        ->assertDontSee('Editar')
        ->assertDontSee('Archivar');
});

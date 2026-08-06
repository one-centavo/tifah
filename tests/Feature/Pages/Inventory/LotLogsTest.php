<?php

declare(strict_types=1);

use App\Models\InventoryMovement;
use App\Models\Lot;
use App\Models\Medicine;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Services\LotService;
use Livewire\Volt\Volt;

test('guest users are redirected to login page from lot logs', function () {
    $user = User::factory()->create();
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

    $medicine = Medicine::factory()->create(['name' => 'Acetaminophen']);

    $lot = Lot::create([
        'medicine_id' => $medicine->id,
        'purchase_order_id' => $purchaseOrder->id,
        'batch_number' => 'BATCH123',
        'expiration_date' => now()->addYear()->toDateString(),
        'current_quantity' => 21,
        'initial_quantity' => 21,
        'reception_date' => now()->toDateString(),
        'unit_purchase_price' => 10.00,
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    $response = $this->get(route('inventory.lots.logs', $lot->id));

    $response->assertRedirect('/login');
});

test('authorized users can access lot logs page', function () {
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

    $medicine = Medicine::factory()->create(['name' => 'Acetaminophen']);

    $lot = Lot::create([
        'medicine_id' => $medicine->id,
        'purchase_order_id' => $purchaseOrder->id,
        'batch_number' => 'BATCH123',
        'expiration_date' => now()->addYear()->toDateString(),
        'current_quantity' => 21,
        'initial_quantity' => 21,
        'reception_date' => now()->toDateString(),
        'unit_purchase_price' => 10.00,
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    $response = $this->get(route('inventory.lots.logs', $lot->id));

    $response
        ->assertOk()
        ->assertSeeVolt('inventory.lot-logs')
        ->assertSee('Historial y Trazabilidad del Lote')
        ->assertSee('Lote: BATCH123');
});

test('it displays existing movement history log with exact details', function () {
    $user = User::factory()->create(['first_name' => 'Jacinto', 'last_name' => 'Perez']);
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

    $medicine = Medicine::factory()->create(['name' => 'Acetaminophen']);

    $lot = Lot::create([
        'medicine_id' => $medicine->id,
        'purchase_order_id' => $purchaseOrder->id,
        'batch_number' => 'BATCH123',
        'expiration_date' => now()->addYear()->toDateString(),
        'current_quantity' => 21,
        'initial_quantity' => 21,
        'reception_date' => now()->toDateString(),
        'unit_purchase_price' => 10.00,
        'status' => 'active',
        'created_by' => $user->id,
    ]);

    $movement = InventoryMovement::create([
        'lot_id' => $lot->id,
        'type' => 'entry',
        'quantity' => 21,
        'previous_balance' => 0,
        'new_balance' => 21,
        'concept' => 'Merchandise reception - Batch BATCH123',
        'reference_id' => $purchaseOrder->id,
        'created_by' => $user->id,
    ]);

    Volt::test('inventory.lot-logs', ['lot' => $lot])
        ->assertSee('21')
        ->assertSee('Registro Inicial')
        ->assertSee('Jacinto Perez');
});

test('administrator can adjust a movement', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $supplier = Supplier::create([
        'nit' => '12345678-9',
        'dv' => 9,
        'name' => 'Supplier Test A',
        'contact_person' => 'John Doe',
        'phone_number' => '1234567',
        'email' => 'supplier@test.com',
        'address' => 'Supplier Address',
        'created_by' => $admin->id,
    ]);

    $purchaseOrder = PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'status' => 'received',
        'expected_date' => now()->toDateString(),
        'received_at' => now()->toDateString(),
        'total_estimated' => 100.00,
        'created_by' => $admin->id,
    ]);

    $medicine = Medicine::factory()->create(['name' => 'Acetaminophen']);

    $lot = Lot::create([
        'medicine_id' => $medicine->id,
        'purchase_order_id' => $purchaseOrder->id,
        'batch_number' => 'BATCH123',
        'expiration_date' => now()->addYear()->toDateString(),
        'current_quantity' => 21,
        'initial_quantity' => 21,
        'reception_date' => now()->toDateString(),
        'unit_purchase_price' => 10.00,
        'status' => 'active',
        'created_by' => $admin->id,
    ]);

    $movement = InventoryMovement::create([
        'lot_id' => $lot->id,
        'type' => 'entry',
        'quantity' => 21,
        'previous_balance' => 0,
        'new_balance' => 21,
        'concept' => 'Merchandise reception - Batch BATCH123',
        'reference_id' => $purchaseOrder->id,
        'created_by' => $admin->id,
    ]);

    Volt::test('inventory.lot-logs', ['lot' => $lot])
        ->call('selectMovementForAdjustment', $movement->id)
        ->set('newQuantity', 20)
        ->set('reason', 'Error de digitación')
        ->set('observations', 'Typo, should be 20 instead of 21')
        ->call('saveAdjustment')
        ->assertHasNoErrors();

    // Verify database updates
    $lot->refresh();
    expect($lot->current_quantity)->toBe(20);

    $adjustMovement = InventoryMovement::where('reference_id', $movement->id)
        ->where('type', 'adjustment')
        ->first();
    expect($adjustMovement)->not->toBeNull()
        ->and($adjustMovement->quantity)->toBe(-1)
        ->and($adjustMovement->previous_balance)->toBe(21)
        ->and($adjustMovement->new_balance)->toBe(20)
        ->and($adjustMovement->adjustment_reason)->toBe('Error de digitación')
        ->and($adjustMovement->observations)->toBe('Typo, should be 20 instead of 21');
});

test('warehouse assistant cannot adjust a movement', function () {
    $admin = User::factory()->admin()->create();
    $assistant = User::factory()->create(); // role is warehouse_assistant by default
    $this->actingAs($assistant);

    $supplier = Supplier::create([
        'nit' => '12345678-9',
        'dv' => 9,
        'name' => 'Supplier Test A',
        'contact_person' => 'John Doe',
        'phone_number' => '1234567',
        'email' => 'supplier@test.com',
        'address' => 'Supplier Address',
        'created_by' => $admin->id,
    ]);

    $purchaseOrder = PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'status' => 'received',
        'expected_date' => now()->toDateString(),
        'received_at' => now()->toDateString(),
        'total_estimated' => 100.00,
        'created_by' => $admin->id,
    ]);

    $medicine = Medicine::factory()->create(['name' => 'Acetaminophen']);

    $lot = Lot::create([
        'medicine_id' => $medicine->id,
        'purchase_order_id' => $purchaseOrder->id,
        'batch_number' => 'BATCH123',
        'expiration_date' => now()->addYear()->toDateString(),
        'current_quantity' => 21,
        'initial_quantity' => 21,
        'reception_date' => now()->toDateString(),
        'unit_purchase_price' => 10.00,
        'status' => 'active',
        'created_by' => $admin->id,
    ]);

    $movement = InventoryMovement::create([
        'lot_id' => $lot->id,
        'type' => 'entry',
        'quantity' => 21,
        'previous_balance' => 0,
        'new_balance' => 21,
        'concept' => 'Merchandise reception - Batch BATCH123',
        'reference_id' => $purchaseOrder->id,
        'created_by' => $admin->id,
    ]);

    // UI should show "Sólo Admin" and hide adjust button triggers
    Volt::test('inventory.lot-logs', ['lot' => $lot])
        ->assertSee('Sólo Admin')
        ->assertDontSee('Ajustar');

    // Trying to trigger adjustment actions directly should abort with a 403
    Volt::test('inventory.lot-logs', ['lot' => $lot])
        ->call('selectMovementForAdjustment', $movement->id)
        ->assertForbidden();
});

test('movements log lists adjustments immediately underneath the original movement', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $supplier = Supplier::create([
        'nit' => '12345678-9',
        'dv' => 9,
        'name' => 'Supplier Test A',
        'contact_person' => 'John Doe',
        'phone_number' => '1234567',
        'email' => 'supplier@test.com',
        'address' => 'Supplier Address',
        'created_by' => $admin->id,
    ]);

    $purchaseOrder = PurchaseOrder::create([
        'supplier_id' => $supplier->id,
        'status' => 'received',
        'expected_date' => now()->toDateString(),
        'received_at' => now()->toDateString(),
        'total_estimated' => 100.00,
        'created_by' => $admin->id,
    ]);

    $medicine = Medicine::factory()->create(['name' => 'Acetaminophen']);

    $lot = Lot::create([
        'medicine_id' => $medicine->id,
        'purchase_order_id' => $purchaseOrder->id,
        'batch_number' => 'BATCH123',
        'expiration_date' => now()->addYear()->toDateString(),
        'current_quantity' => 40,
        'initial_quantity' => 40,
        'reception_date' => now()->toDateString(),
        'unit_purchase_price' => 10.00,
        'status' => 'active',
        'created_by' => $admin->id,
    ]);

    // Create entry movement A (ID 1 equivalent)
    $movementA = InventoryMovement::create([
        'lot_id' => $lot->id,
        'type' => 'entry',
        'quantity' => 20,
        'previous_balance' => 0,
        'new_balance' => 20,
        'concept' => 'Movement A',
        'reference_id' => $purchaseOrder->id,
        'created_by' => $admin->id,
    ]);

    // Create entry movement B (ID 2 equivalent)
    $movementB = InventoryMovement::create([
        'lot_id' => $lot->id,
        'type' => 'entry',
        'quantity' => 20,
        'previous_balance' => 20,
        'new_balance' => 40,
        'concept' => 'Movement B',
        'reference_id' => $purchaseOrder->id,
        'created_by' => $admin->id,
    ]);

    // Perform adjustment on movement A (pointing to parent ID 1)
    $lotService = app(LotService::class);
    $lotService->adjustMovement($movementA, 19, 'Error de digitación', 'Correction A', $admin->id);

    // Retrieve sorted movements from database with the same query as the component
    $movementsList = $lot->inventoryMovements()
        ->orderByRaw("CASE WHEN type = 'adjustment' THEN reference_id ELSE id END ASC")
        ->orderBy('id', 'ASC')
        ->get();

    // Expected order: Movement A (ID 1), Adjustment of A (ID 3), Movement B (ID 2)
    expect($movementsList[0]->id)->toBe($movementA->id);
    expect($movementsList[1]->reference_id)->toBe($movementA->id);
    expect($movementsList[1]->type)->toBe('adjustment');
    expect($movementsList[2]->id)->toBe($movementB->id);

    // Verify component renders all of them
    Volt::test('inventory.lot-logs', ['lot' => $lot])
        ->assertSee('Movement A')
        ->assertSee('Movement B')
        ->assertSee('Correction A');
});

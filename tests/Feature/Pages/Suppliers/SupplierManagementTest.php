<?php

declare(strict_types=1);

use App\Models\Lot;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use Livewire\Volt\Volt;

test('guest users are redirected to login page from index, create, and edit', function () {
    $this->get(route('suppliers.index'))->assertRedirect('/login');
    $this->get(route('suppliers.create'))->assertRedirect('/login');

    $supplier = Supplier::factory()->create();
    $this->get(route('suppliers.edit', $supplier->id))->assertRedirect('/login');
});

test('authorized users can access suppliers index, create, and edit pages', function () {
    $user = User::factory()->create();
    $supplier = Supplier::factory()->create();

    $this->actingAs($user);

    $this->get(route('suppliers.index'))
        ->assertOk()
        ->assertSeeVolt('suppliers.index');

    $this->get(route('suppliers.create'))
        ->assertOk()
        ->assertSeeVolt('suppliers.create');

    $this->get(route('suppliers.edit', $supplier->id))
        ->assertOk()
        ->assertSeeVolt('suppliers.edit');
});

test('it displays the list of active suppliers', function () {
    $user = User::factory()->create();
    Supplier::factory()->create(['name' => 'Supplier Alpha', 'nit' => '123.456.789']);
    Supplier::factory()->create(['name' => 'Supplier Beta', 'nit' => '987.654.321']);

    $this->actingAs($user);

    Volt::test('suppliers.index')
        ->assertSee('Supplier Alpha')
        ->assertSee('123.456.789')
        ->assertSee('Supplier Beta')
        ->assertSee('987.654.321');
});

test('it can search suppliers by Razón Social or NIT', function () {
    $user = User::factory()->create();
    Supplier::factory()->create(['name' => 'Acme Corp', 'nit' => '111.222.333']);
    Supplier::factory()->create(['name' => 'Globex Inc', 'nit' => '444.555.666']);

    $this->actingAs($user);

    // Search by name
    Volt::test('suppliers.index', ['search' => 'Acme'])
        ->assertSee('Acme Corp')
        ->assertDontSee('Globex Inc');

    // Search by NIT
    Volt::test('suppliers.index', ['search' => '444.555'])
        ->assertSee('Globex Inc')
        ->assertDontSee('Acme Corp');
});

test('it can filter suppliers by status', function () {
    $user = User::factory()->create();
    $active = Supplier::factory()->create(['name' => 'Active Supplier']);
    $archived = Supplier::factory()->create(['name' => 'Archived Supplier']);
    $archived->delete(); // Soft delete

    $this->actingAs($user);

    // Default status: active
    Volt::test('suppliers.index')
        ->assertSee('Active Supplier')
        ->assertDontSee('Archived Supplier');

    // Status: archived
    Volt::test('suppliers.index', ['status' => 'archived'])
        ->assertSee('Archived Supplier')
        ->assertDontSee('Active Supplier');

    // Status: all
    Volt::test('suppliers.index', ['status' => 'all'])
        ->assertSee('Active Supplier')
        ->assertSee('Archived Supplier');
});

test('it calculates the verification digit reactively while typing NIT', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('suppliers.create')
        ->set('nit', '890.903.938')
        ->assertSet('dv', '8')
        ->set('nit', '900123456')
        ->assertSet('dv', '8')
        ->set('nit', '')
        ->assertSet('dv', '');
});

test('it validates fields on create', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    // Test required fields
    Volt::test('suppliers.create')
        ->set('nit', '')
        ->set('name', '')
        ->set('phone_number', '')
        ->set('email', '')
        ->set('address', '')
        ->call('save')
        ->assertHasErrors([
            'nit' => 'required',
            'name' => 'required',
            'phone_number' => 'required',
            'email' => 'required',
            'address' => 'required',
        ]);

    // Test email format and lengths
    Volt::test('suppliers.create')
        ->set('email', 'not-an-email')
        ->set('address', str_repeat('a', 201))
        ->set('name', str_repeat('a', 151))
        ->set('nit', 'invalid-format')
        ->call('save')
        ->assertHasErrors([
            'email' => 'email',
            'address' => 'max',
            'name' => 'max',
            'nit' => 'regex',
        ]);
});

test('it validates unique NIT on create', function () {
    $user = User::factory()->create();
    Supplier::factory()->create(['nit' => '900.123.456']);

    $this->actingAs($user);

    Volt::test('suppliers.create')
        ->set('nit', '900.123.456')
        ->call('save')
        ->assertHasErrors(['nit' => 'unique'])
        ->assertSee('Este NIT ya se encuentra registrado.');
});

test('it successfully creates a supplier and logs creator', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('suppliers.create')
        ->set('nit', '900.123.456')
        ->set('name', 'Farmaceutica SAS')
        ->set('contact_person', 'Juan Perez') // optional
        ->set('phone_number', '3001234567')
        ->set('email', 'juan@farmaceutica.com')
        ->set('address', 'Calle Falsa 123')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('suppliers', [
        'nit' => '900.123.456',
        'dv' => 8,
        'name' => 'Farmaceutica SAS',
        'contact_person' => 'Juan Perez',
        'phone_number' => '3001234567',
        'email' => 'juan@farmaceutica.com',
        'address' => 'Calle Falsa 123',
        'created_by' => $user->id,
        'updated_by' => null,
    ]);
});

test('it can edit a supplier and log updater while preserving creator', function () {
    $creator = User::factory()->create();
    $updater = User::factory()->create();
    $supplier = Supplier::factory()->create([
        'nit' => '890.903.938',
        'created_by' => $creator->id,
    ]);

    $this->actingAs($updater);

    Volt::test('suppliers.edit', ['supplier' => $supplier])
        ->set('name', 'Updated Name Corp')
        ->set('contact_person', 'New Contact')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('suppliers', [
        'id' => $supplier->id,
        'name' => 'Updated Name Corp',
        'contact_person' => 'New Contact',
        'created_by' => $creator->id,
        'updated_by' => $updater->id,
    ]);
});

test('it allows keeping the same NIT when editing the supplier itself', function () {
    $user = User::factory()->create();
    $supplier = Supplier::factory()->create(['nit' => '900.123.456']);

    $this->actingAs($user);

    Volt::test('suppliers.edit', ['supplier' => $supplier])
        ->set('name', 'New Name')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('suppliers', [
        'id' => $supplier->id,
        'nit' => '900.123.456',
        'name' => 'New Name',
    ]);
});

test('it validates unique NIT against other suppliers when editing', function () {
    $user = User::factory()->create();
    $supplier1 = Supplier::factory()->create(['nit' => '900.123.456']);
    $supplier2 = Supplier::factory()->create(['nit' => '890.903.938']);

    $this->actingAs($user);

    Volt::test('suppliers.edit', ['supplier' => $supplier2])
        ->set('nit', '900.123.456')
        ->call('save')
        ->assertHasErrors(['nit' => 'unique'])
        ->assertSee('Este NIT ya se encuentra registrado.');
});

test('it soft deletes a supplier and logs deleter if no active lots exist', function () {
    $user = User::factory()->create();
    $supplier = Supplier::factory()->create();

    $this->actingAs($user);

    Volt::test('suppliers.index')
        ->call('confirmSupplierDeletion', $supplier->id)
        ->call('deleteSupplier')
        ->assertHasNoErrors();

    $this->assertSoftDeleted('suppliers', [
        'id' => $supplier->id,
        'deleted_by' => $user->id,
    ]);
});

test('it blocks soft delete if supplier has active inventory lots', function () {
    $user = User::factory()->create();
    $supplier = Supplier::factory()->create();

    $purchaseOrder = PurchaseOrder::factory()->create([
        'supplier_id' => $supplier->id,
    ]);

    // Active lot with quantity > 0
    Lot::factory()->create([
        'purchase_order_id' => $purchaseOrder->id,
        'current_quantity' => 10,
    ]);

    $this->actingAs($user);

    // Attempt delete
    Volt::test('suppliers.index')
        ->call('confirmSupplierDeletion', $supplier->id)
        ->call('deleteSupplier')
        ->assertHasErrors(['deletion_error'])
        ->assertSee('No se puede eliminar el proveedor porque tiene lotes de mercancía activos en el inventario.');

    // Verify supplier is NOT soft-deleted
    $this->assertDatabaseHas('suppliers', [
        'id' => $supplier->id,
        'deleted_at' => null,
    ]);
});

test('it allows registering a supplier with an NIT of a soft-deleted supplier', function () {
    $user = User::factory()->create();
    $archivedSupplier = Supplier::factory()->create(['nit' => '900.123.456']);
    $archivedSupplier->delete(); // Soft delete

    $this->actingAs($user);

    Volt::test('suppliers.create')
        ->set('nit', '900.123.456')
        ->set('name', 'New Supplier Corp')
        ->set('phone_number', '3001234567')
        ->set('email', 'new@supplier.com')
        ->set('address', 'Calle 100')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('suppliers', [
        'nit' => '900.123.456',
        'name' => 'New Supplier Corp',
        'deleted_at' => null,
    ]);
});

test('it allows updating a supplier to have an NIT of a soft-deleted supplier', function () {
    $user = User::factory()->create();
    $archivedSupplier = Supplier::factory()->create(['nit' => '900.123.456']);
    $archivedSupplier->delete(); // Soft delete

    $activeSupplier = Supplier::factory()->create(['nit' => '890.903.938']);

    $this->actingAs($user);

    Volt::test('suppliers.edit', ['supplier' => $activeSupplier])
        ->set('nit', '900.123.456')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('suppliers', [
        'id' => $activeSupplier->id,
        'nit' => '900.123.456',
    ]);
});

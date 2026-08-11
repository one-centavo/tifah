<?php

declare(strict_types=1);

use App\Models\Bill;
use App\Models\Customer;
use App\Models\User;
use Livewire\Volt\Volt;

test('guest users are redirected to login page from index, create, and edit', function () {
    $this->get(route('customers.index'))->assertRedirect('/login');
    $this->get(route('customers.create'))->assertRedirect('/login');

    $customer = Customer::factory()->create();
    $this->get(route('customers.edit', $customer->id))->assertRedirect('/login');
});

test('authorized users can access customers index, create, and edit pages', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();

    $this->actingAs($user);

    $this->get(route('customers.index'))
        ->assertOk()
        ->assertSeeVolt('customers.index');

    $this->get(route('customers.create'))
        ->assertOk()
        ->assertSeeVolt('customers.create');

    $this->get(route('customers.edit', $customer->id))
        ->assertOk()
        ->assertSeeVolt('customers.edit');
});

test('it displays the list of active customers', function () {
    $user = User::factory()->create();
    Customer::factory()->create(['name' => 'Customer Alpha', 'nit' => '123.456.789', 'is_active' => 1]);
    Customer::factory()->create(['name' => 'Customer Beta', 'nit' => '987.654.321', 'is_active' => 1]);

    $this->actingAs($user);

    Volt::test('customers.index')
        ->assertSee('Customer Alpha')
        ->assertSee('123.456.789')
        ->assertSee('Customer Beta')
        ->assertSee('987.654.321');
});

test('it can search customers by Razón Social or NIT', function () {
    $user = User::factory()->create();
    Customer::factory()->create(['name' => 'Acme Corp', 'nit' => '111.222.333', 'is_active' => 1]);
    Customer::factory()->create(['name' => 'Globex Inc', 'nit' => '444.555.666', 'is_active' => 1]);

    $this->actingAs($user);

    // Search by name
    Volt::test('customers.index', ['search' => 'Acme'])
        ->assertSee('Acme Corp')
        ->assertDontSee('Globex Inc');

    // Search by NIT
    Volt::test('customers.index', ['search' => '444.555'])
        ->assertSee('Globex Inc')
        ->assertDontSee('Acme Corp');
});

test('it can filter customers by status', function () {
    $user = User::factory()->create();
    $active = Customer::factory()->create(['name' => 'Active Customer', 'is_active' => 1]);
    $inactive = Customer::factory()->create(['name' => 'Inactive Customer', 'is_active' => 0]);
    $archived = Customer::factory()->create(['name' => 'Archived Customer', 'is_active' => 1]);
    $archived->delete(); // Soft delete

    $this->actingAs($user);

    // Default status: active
    Volt::test('customers.index')
        ->assertSee('Active Customer')
        ->assertDontSee('Inactive Customer')
        ->assertDontSee('Archived Customer');

    // Status: inactive
    Volt::test('customers.index', ['status' => 'inactive'])
        ->assertSee('Inactive Customer')
        ->assertDontSee('Active Customer')
        ->assertDontSee('Archived Customer');

    // Status: archived
    Volt::test('customers.index', ['status' => 'archived'])
        ->assertSee('Archived Customer')
        ->assertDontSee('Active Customer')
        ->assertDontSee('Inactive Customer');

    // Status: all
    Volt::test('customers.index', ['status' => 'all'])
        ->assertSee('Active Customer')
        ->assertSee('Inactive Customer')
        ->assertSee('Archived Customer');
});

test('it can filter customers by city', function () {
    $user = User::factory()->create();
    Customer::factory()->create(['name' => 'Bogota Pharmacy', 'city' => 'Bogotá', 'is_active' => 1]);
    Customer::factory()->create(['name' => 'Medellin Drugstore', 'city' => 'Medellín', 'is_active' => 1]);

    $this->actingAs($user);

    Volt::test('customers.index', ['city' => 'Bogotá'])
        ->assertSee('Bogota Pharmacy')
        ->assertDontSee('Medellin Drugstore');

    Volt::test('customers.index', ['city' => 'Medellín'])
        ->assertSee('Medellin Drugstore')
        ->assertDontSee('Bogota Pharmacy');
});

test('it can sort customers by Razón Social', function () {
    $user = User::factory()->create();
    $customerB = Customer::factory()->create(['name' => 'Beta Pharmacy', 'is_active' => 1]);
    $customerA = Customer::factory()->create(['name' => 'Alpha Pharmacy', 'is_active' => 1]);

    $this->actingAs($user);

    Volt::test('customers.index')
        ->call('sortBy', 'name')
        ->assertSet('sortField', 'name')
        ->assertSet('sortDirection', 'desc')
        ->call('sortBy', 'name')
        ->assertSet('sortDirection', 'asc');
});

test('it displays empty state message when no customers match applied filters', function () {
    $user = User::factory()->create();
    Customer::factory()->create(['name' => 'Farmacia Unica', 'city' => 'Cali', 'is_active' => 1]);

    $this->actingAs($user);

    Volt::test('customers.index', ['search' => 'Inexistente'])
        ->assertSee('Sin resultados')
        ->assertSee('No se encontraron clientes que coincidan con los filtros aplicados');

    Volt::test('customers.index', ['city' => 'Cartagena'])
        ->assertSee('Sin resultados')
        ->assertSee('No se encontraron clientes que coincidan con los filtros aplicados');
});

test('it calculates the verification digit reactively while typing NIT', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('customers.create')
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
    Volt::test('customers.create')
        ->set('nit', '')
        ->set('name', '')
        ->set('city', '')
        ->set('address', '')
        ->set('phone_number', '')
        ->set('email', '')
        ->call('save')
        ->assertHasErrors([
            'nit' => 'required',
            'name' => 'required',
            'city' => 'required',
            'address' => 'required',
            'phone_number' => 'required',
            'email' => 'required',
        ]);

    // Test email format and lengths
    Volt::test('customers.create')
        ->set('email', 'not-an-email')
        ->set('address', str_repeat('a', 256))
        ->set('name', str_repeat('a', 256))
        ->set('city', str_repeat('a', 101))
        ->set('nit', 'invalid-format')
        ->call('save')
        ->assertHasErrors([
            'email' => 'email',
            'address' => 'max',
            'name' => 'max',
            'city' => 'max',
            'nit' => 'regex',
        ]);
});

test('it validates unique NIT on create', function () {
    $user = User::factory()->create();
    Customer::factory()->create(['nit' => '900.123.456', 'is_active' => 1]);

    $this->actingAs($user);

    Volt::test('customers.create')
        ->set('nit', '900.123.456')
        ->call('save')
        ->assertHasErrors(['nit' => 'unique'])
        ->assertSee('Este NIT ya se encuentra registrado.');
});

test('it successfully creates a customer and logs creator', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('customers.create')
        ->set('nit', '900.123.456')
        ->set('name', 'Farmacia Cruz Verde Principal')
        ->set('city', 'Medellín')
        ->set('address', 'Calle 50 # 10-20')
        ->set('phone_number', '3007654321')
        ->set('email', 'factura@cruzverde.com')
        ->set('is_active', true)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('customers', [
        'nit' => '900.123.456',
        'dv' => 8,
        'name' => 'Farmacia Cruz Verde Principal',
        'city' => 'Medellín',
        'address' => 'Calle 50 # 10-20',
        'phone_number' => '3007654321',
        'email' => 'factura@cruzverde.com',
        'is_active' => true,
        'created_by' => $user->id,
        'updated_by' => null,
    ]);
});

test('it can edit a customer and log updater while preserving creator', function () {
    $creator = User::factory()->create();
    $updater = User::factory()->create();
    $customer = Customer::factory()->create([
        'nit' => '890.903.938',
        'created_by' => $creator->id,
    ]);

    $this->actingAs($updater);

    Volt::test('customers.edit', ['customer' => $customer])
        ->set('name', 'Updated Customer Name')
        ->set('city', 'Cali')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'name' => 'Updated Customer Name',
        'city' => 'Cali',
        'created_by' => $creator->id,
        'updated_by' => $updater->id,
    ]);
});

test('it allows keeping the same NIT when editing the customer itself', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create(['nit' => '900.123.456']);

    $this->actingAs($user);

    Volt::test('customers.edit', ['customer' => $customer])
        ->set('name', 'New Name')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'nit' => '900.123.456',
        'name' => 'New Name',
    ]);
});

test('it validates unique NIT against other customers when editing', function () {
    $user = User::factory()->create();
    $customer1 = Customer::factory()->create(['nit' => '900.123.456']);
    $customer2 = Customer::factory()->create(['nit' => '890.903.938']);

    $this->actingAs($user);

    Volt::test('customers.edit', ['customer' => $customer2])
        ->set('nit', '900.123.456')
        ->call('save')
        ->assertHasErrors(['nit' => 'unique'])
        ->assertSee('Este NIT ya se encuentra registrado.');
});

test('it soft deletes a customer and logs deleter if no bills exist', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();

    $this->actingAs($user);

    Volt::test('customers.index')
        ->call('confirmCustomerDeletion', $customer->id)
        ->call('deleteCustomer')
        ->assertHasNoErrors();

    $this->assertSoftDeleted('customers', [
        'id' => $customer->id,
        'deleted_by' => $user->id,
    ]);
});

test('it blocks soft delete if customer has associated bills', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();

    // Create an associated bill
    Bill::factory()->create([
        'id_customer' => $customer->id,
        'created_by' => $user->id,
    ]);

    $this->actingAs($user);

    // Attempt delete
    Volt::test('customers.index')
        ->call('confirmCustomerDeletion', $customer->id)
        ->call('deleteCustomer')
        ->assertHasErrors(['deletion_error'])
        ->assertSee('No se puede eliminar el cliente porque tiene facturas asociadas en el histórico.');

    // Verify customer is NOT soft-deleted
    $this->assertDatabaseHas('customers', [
        'id' => $customer->id,
        'deleted_at' => null,
    ]);
});

test('it allows registering a customer with an NIT of a soft-deleted customer', function () {
    $user = User::factory()->create();
    $archivedCustomer = Customer::factory()->create(['nit' => '900.123.456']);
    $archivedCustomer->delete(); // Soft delete

    $this->actingAs($user);

    Volt::test('customers.create')
        ->set('nit', '900.123.456')
        ->set('name', 'New Customer Corp')
        ->set('city', 'Barranquilla')
        ->set('address', 'Calle 80')
        ->set('phone_number', '3009876543')
        ->set('email', 'new@customer.com')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('customers', [
        'nit' => '900.123.456',
        'name' => 'New Customer Corp',
        'deleted_at' => null,
    ]);
});

test('it allows updating a customer to have an NIT of a soft-deleted customer', function () {
    $user = User::factory()->create();
    $archivedCustomer = Customer::factory()->create(['nit' => '900.123.456']);
    $archivedCustomer->delete(); // Soft delete

    $activeCustomer = Customer::factory()->create(['nit' => '890.903.938']);

    $this->actingAs($user);

    Volt::test('customers.edit', ['customer' => $activeCustomer])
        ->set('nit', '900.123.456')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('customers', [
        'id' => $activeCustomer->id,
        'nit' => '900.123.456',
    ]);
});

<?php

declare(strict_types=1);

use App\Models\Bill;
use App\Models\BillDetail;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Lot;
use App\Models\Medicine;
use App\Models\User;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('guest users are redirected to login page from bills index', function () {
    auth()->logout();

    $this->get(route('bills.index'))
        ->assertRedirect(route('login'));
});

test('authorized users can access bills management page', function () {
    $this->get(route('bills.index'))
        ->assertOk()
        ->assertSee('Facturas y Salidas de Mercancía');
});

test('it displays the list of invoices with customer and payment info', function () {
    $customer = Customer::factory()->create(['name' => 'Farmacia El Ahorro']);
    $bill = Bill::factory()->create([
        'id_customer' => $customer->id,
        'invoice_number' => 'FAC-100200',
        'status' => 'active',
        'payment_method' => 'cash',
        'total_amount' => 45000,
    ]);

    Volt::test('bills.index')
        ->assertSee('FAC-100200')
        ->assertSee('Farmacia El Ahorro')
        ->assertSee('Efectivo')
        ->assertSee('45.000');
});

test('it can search bills by invoice number and customer name', function () {
    $customer1 = Customer::factory()->create(['name' => 'Droguería Central']);
    $customer2 = Customer::factory()->create(['name' => 'Clínica Los Andes']);

    Bill::factory()->create([
        'id_customer' => $customer1->id,
        'invoice_number' => 'FAC-000111',
    ]);

    Bill::factory()->create([
        'id_customer' => $customer2->id,
        'invoice_number' => 'FAC-000222',
    ]);

    Volt::test('bills.index')
        ->set('search', 'FAC-000111')
        ->assertSee('FAC-000111')
        ->assertDontSee('FAC-000222')
        ->set('search', 'Los Andes')
        ->assertSee('FAC-000222')
        ->assertDontSee('FAC-000111');
});

test('it can filter bills by status and payment method', function () {
    $customer = Customer::factory()->create();

    $activeCash = Bill::factory()->create([
        'id_customer' => $customer->id,
        'invoice_number' => 'FAC-ACTIVE-CASH',
        'status' => 'active',
        'payment_method' => 'cash',
    ]);

    $annulledCredit = Bill::factory()->annulled()->create([
        'id_customer' => $customer->id,
        'invoice_number' => 'FAC-ANNULLED-CREDIT',
        'payment_method' => 'credit',
    ]);

    Volt::test('bills.index')
        ->set('status', 'active')
        ->assertSee('FAC-ACTIVE-CASH')
        ->assertDontSee('FAC-ANNULLED-CREDIT')
        ->set('status', 'annulled')
        ->assertSee('FAC-ANNULLED-CREDIT')
        ->assertDontSee('FAC-ACTIVE-CASH')
        ->set('status', 'all')
        ->set('paymentMethod', 'credit')
        ->assertSee('FAC-ANNULLED-CREDIT')
        ->assertDontSee('FAC-ACTIVE-CASH');
});

test('it can open bill detail modal and display itemized batches', function () {
    $customer = Customer::factory()->create(['name' => 'Farmacia Pasteur']);
    $medicine = Medicine::factory()->create(['name' => 'Dolex 500mg']);
    $lot = Lot::factory()->create([
        'medicine_id' => $medicine->id,
        'batch_number' => 'DLX-999',
    ]);

    $bill = Bill::factory()->create([
        'id_customer' => $customer->id,
        'invoice_number' => 'FAC-DETAIL-TEST',
    ]);

    BillDetail::create([
        'bill_id' => $bill->id,
        'lot_id' => $lot->id,
        'quantity' => 12,
        'unit_price' => 1500,
        'subtotal' => 18000,
    ]);

    Volt::test('bills.index')
        ->call('openDetailModal', $bill->id)
        ->assertSet('showDetailModal', true)
        ->assertSee('FAC-DETAIL-TEST')
        ->assertSee('Farmacia Pasteur')
        ->assertSee('Dolex 500mg')
        ->assertSee('DLX-999')
        ->assertSee('12');
});

test('it can annul an active bill, return stock to lots, and create inventory movements', function () {
    $customer = Customer::factory()->create();
    $medicine = Medicine::factory()->create();
    $lot = Lot::factory()->create([
        'medicine_id' => $medicine->id,
        'batch_number' => 'LOT-TO-RESTORE',
        'current_quantity' => 10,
    ]);

    $bill = Bill::factory()->create([
        'id_customer' => $customer->id,
        'status' => 'active',
        'invoice_number' => 'FAC-TO-ANNUL',
        'total_amount' => 50000,
    ]);

    BillDetail::create([
        'bill_id' => $bill->id,
        'lot_id' => $lot->id,
        'quantity' => 5,
        'unit_price' => 10000,
        'subtotal' => 50000,
    ]);

    Volt::test('bills.index')
        ->call('openAnnulModal', $bill->id)
        ->assertSet('showAnnulModal', true)
        ->set('annulmentReason', 'El cliente solicitó cancelar el pedido por duplicidad')
        ->call('annulBill')
        ->assertHasNoErrors()
        ->assertSet('showAnnulModal', false);

    $bill->refresh();
    expect($bill->status)->toBe('annulled');
    expect($bill->annulled_reason)->toBe('El cliente solicitó cancelar el pedido por duplicidad');

    // Lot stock returned
    $lot->refresh();
    expect($lot->current_quantity)->toBe(15);

    // Compensating movement logged
    $movement = InventoryMovement::where('reference_id', $bill->id)->where('type', 'entry')->first();
    expect($movement)->not->toBeNull();
    expect($movement->quantity)->toBe(5);
    expect($movement->new_balance)->toBe(15);
});

test('it validates annulment reason is required', function () {
    $bill = Bill::factory()->create(['status' => 'active']);

    Volt::test('bills.index')
        ->call('openAnnulModal', $bill->id)
        ->set('annulmentReason', '')
        ->call('annulBill')
        ->assertHasErrors(['annulmentReason']);
});

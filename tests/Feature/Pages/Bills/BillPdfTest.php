<?php

declare(strict_types=1);

use App\Models\Bill;
use App\Models\BillDetail;
use App\Models\Customer;
use App\Models\Lot;
use App\Models\Medicine;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('guest users are redirected from bill pdf route', function () {
    auth()->logout();
    $bill = Bill::factory()->create();

    $this->get(route('bills.pdf', $bill->id))
        ->assertRedirect(route('login'));
});

test('authorized users can view and download invoice pdf with all required data', function () {
    $customer = Customer::factory()->create([
        'name' => 'Farmacia San Antonio',
        'nit' => '900.555.444',
        'dv' => 8,
        'city' => 'Medellín',
        'address' => 'Carrera 43A # 1-50',
    ]);

    $medicine = Medicine::factory()->create([
        'name' => 'Acetaminofén 500mg',
    ]);

    $lot = Lot::factory()->create([
        'medicine_id' => $medicine->id,
        'batch_number' => 'ACT-BATCH-777',
        'expiration_date' => '2027-12-31',
    ]);

    $bill = Bill::factory()->create([
        'id_customer' => $customer->id,
        'invoice_number' => 'FAC-999001',
        'payment_method' => 'credit',
        'payment_due_date' => now()->addDays(30),
        'total_amount' => 125000,
        'status' => 'active',
    ]);

    BillDetail::create([
        'bill_id' => $bill->id,
        'lot_id' => $lot->id,
        'quantity' => 25,
        'unit_price' => 5000,
        'subtotal' => 125000,
    ]);

    $response = $this->get(route('bills.pdf', $bill->id));

    $response->assertOk()
        ->assertSee('TIFAH')
        ->assertSee('FAC-999001')
        ->assertSee('Farmacia San Antonio')
        ->assertSee('900.555.444-8')
        ->assertSee('Medellín')
        ->assertSee('Carrera 43A # 1-50')
        ->assertSee('Acetaminofén 500mg')
        ->assertSee('ACT-BATCH-777')
        ->assertSee('2027-12-31')
        ->assertSee('25')
        ->assertSee('125.000')
        ->assertSee('Crédito Comercial');
});

test('it displays annulment banner and reason when viewing pdf of an annulled bill', function () {
    $customer = Customer::factory()->create();
    $bill = Bill::factory()->annulled()->create([
        'id_customer' => $customer->id,
        'invoice_number' => 'FAC-ANNULLED-PDF',
        'annulled_reason' => 'Mercancía devuelta por daño en transporte',
    ]);

    $response = $this->get(route('bills.pdf', $bill->id));

    $response->assertOk()
        ->assertSee('FACTURA ANULADA')
        ->assertSee('Mercancía devuelta por daño en transporte');
});

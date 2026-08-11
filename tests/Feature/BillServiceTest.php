<?php

use App\Models\Bill;
use App\Models\BillDetail;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Lot;
use App\Models\Medicine;
use App\Models\User;
use App\Services\BillService;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->billService = app(BillService::class);
    $this->user = User::factory()->create(['role' => 'Auxiliar de Bodega']);
    $this->actingAs($this->user);
});

test('it allocates lots following FEFO chronological order', function () {
    $medicine = Medicine::factory()->create(['selling_price' => 15000]);

    // Lot 1: expires in 60 days, stock 10
    $lot1 = Lot::factory()->create([
        'medicine_id' => $medicine->id,
        'batch_number' => 'LOT-EXP-60',
        'expiration_date' => now()->addDays(60)->toDateString(),
        'current_quantity' => 10,
        'status' => 'active',
    ]);

    // Lot 2: expires in 10 days, stock 5 (Earliest!)
    $lot2 = Lot::factory()->create([
        'medicine_id' => $medicine->id,
        'batch_number' => 'LOT-EXP-10',
        'expiration_date' => now()->addDays(10)->toDateString(),
        'current_quantity' => 5,
        'status' => 'active',
    ]);

    // Lot 3: expires in 30 days, stock 15
    $lot3 = Lot::factory()->create([
        'medicine_id' => $medicine->id,
        'batch_number' => 'LOT-EXP-30',
        'expiration_date' => now()->addDays(30)->toDateString(),
        'current_quantity' => 15,
        'status' => 'active',
    ]);

    // Request 12 units -> Should take 5 from Lot2 (10d), and 7 from Lot3 (30d)
    $result = $this->billService->allocateFefoLots($medicine, 12);

    expect($result['fulfilled_quantity'])->toBe(12);
    expect($result['shortfall_quantity'])->toBe(0);
    expect($result['allocations'])->toHaveCount(2);

    expect($result['allocations'][0]['lot_id'])->toBe($lot2->id);
    expect($result['allocations'][0]['allocated_quantity'])->toBe(5);
    expect($result['allocations'][0]['is_fefo_priority'])->toBeTrue();

    expect($result['allocations'][1]['lot_id'])->toBe($lot3->id);
    expect($result['allocations'][1]['allocated_quantity'])->toBe(7);
});

test('it respects locked lots when calculating FEFO allocations', function () {
    $medicine = Medicine::factory()->create(['selling_price' => 10000]);

    $lot1 = Lot::factory()->create([
        'medicine_id' => $medicine->id,
        'batch_number' => 'LOT-10D',
        'expiration_date' => now()->addDays(10)->toDateString(),
        'current_quantity' => 10,
        'status' => 'active',
    ]);

    $lot2 = Lot::factory()->create([
        'medicine_id' => $medicine->id,
        'batch_number' => 'LOT-50D',
        'expiration_date' => now()->addDays(50)->toDateString(),
        'current_quantity' => 20,
        'status' => 'active',
    ]);

    // User locks 8 units from lot2 manually, requests total of 15 units
    $lockedAssignments = [$lot2->id => 8];

    $result = $this->billService->allocateFefoLots($medicine, 15, $lockedAssignments);

    expect($result['fulfilled_quantity'])->toBe(15);
    expect($result['shortfall_quantity'])->toBe(0);

    $lot2Alloc = collect($result['allocations'])->firstWhere('lot_id', $lot2->id);
    $lot1Alloc = collect($result['allocations'])->firstWhere('lot_id', $lot1->id);

    expect($lot2Alloc['allocated_quantity'])->toBe(8);
    expect($lot2Alloc['is_locked'])->toBeTrue();

    expect($lot1Alloc['allocated_quantity'])->toBe(7);
    expect($lot1Alloc['is_locked'])->toBeFalse();
});

test('it validates credit eligibility against customer credit limit', function () {
    $customer = Customer::factory()->create([
        'credit_limit' => 100000,
    ]);

    // Existing active credit bill of 60,000
    Bill::factory()->create([
        'id_customer' => $customer->id,
        'status' => 'active',
        'payment_method' => 'credit',
        'total_amount' => 60000,
    ]);

    // Sale of 30,000 (total debt 90,000 <= 100,000) should pass
    $this->billService->validateCreditEligibility($customer, 30000);

    // Sale of 50,000 (total debt 110,000 > 100,000) should fail
    expect(fn () => $this->billService->validateCreditEligibility($customer, 50000))
        ->toThrow(ValidationException::class);
});

test('it creates a sale atomically, discounts stock, and logs inventory movements', function () {
    $customer = Customer::factory()->create();
    $medicine = Medicine::factory()->create(['selling_price' => 20000]);

    $lot = Lot::factory()->create([
        'medicine_id' => $medicine->id,
        'batch_number' => 'LOT-SALE-1',
        'current_quantity' => 50,
        'status' => 'active',
    ]);

    $billData = [
        'id_customer' => $customer->id,
        'payment_method' => 'cash',
    ];

    $items = [
        [
            'lot_id' => $lot->id,
            'quantity' => 10,
            'unit_price' => 20000,
        ],
    ];

    $bill = $this->billService->createSale($billData, $items, $this->user->id);

    expect($bill)->toBeInstanceOf(Bill::class);
    expect($bill->status)->toBe('active');
    expect((float) $bill->total_amount)->toBe(200000.0);
    expect($bill->invoice_number)->toStartWith('FAC-');

    // Lot current quantity decremented
    $lot->refresh();
    expect($lot->current_quantity)->toBe(40);

    // Bill detail created
    expect(BillDetail::where('bill_id', $bill->id)->count())->toBe(1);

    // Inventory movement created
    $movement = InventoryMovement::where('reference_id', $bill->id)->first();
    expect($movement)->not->toBeNull();
    expect($movement->type)->toBe('exit');
    expect($movement->quantity)->toBe(10);
    expect($movement->previous_balance)->toBe(50);
    expect($movement->new_balance)->toBe(40);
});

test('it annuls a bill, restores stock, and creates compensating inventory movements', function () {
    $customer = Customer::factory()->create();
    $medicine = Medicine::factory()->create(['selling_price' => 15000]);

    $lot = Lot::factory()->create([
        'medicine_id' => $medicine->id,
        'batch_number' => 'LOT-ANNUL-1',
        'current_quantity' => 20,
        'status' => 'active',
    ]);

    // Create sale of 5 units
    $bill = $this->billService->createSale(
        ['id_customer' => $customer->id, 'payment_method' => 'cash'],
        [['lot_id' => $lot->id, 'quantity' => 5, 'unit_price' => 15000]],
        $this->user->id
    );

    $lot->refresh();
    expect($lot->current_quantity)->toBe(15);

    // Annul the bill
    $annulledBill = $this->billService->annulBill($bill, 'Cliente canceló la orden', $this->user->id);

    expect($annulledBill->status)->toBe('annulled');
    expect($annulledBill->annulled_reason)->toBe('Cliente canceló la orden');
    expect($annulledBill->annulled_by)->toBe($this->user->id);
    expect($annulledBill->annulled_at)->not->toBeNull();

    // Lot quantity restored
    $lot->refresh();
    expect($lot->current_quantity)->toBe(20);

    // Check annulment inventory movement
    $entryMovement = InventoryMovement::where('reference_id', $bill->id)
        ->where('type', 'entry')
        ->first();

    expect($entryMovement)->not->toBeNull();
    expect($entryMovement->quantity)->toBe(5);
    expect($entryMovement->previous_balance)->toBe(15);
    expect($entryMovement->new_balance)->toBe(20);
});

test('it throws validation exception when trying to annul a non-active bill', function () {
    $bill = Bill::factory()->annulled()->create();

    expect(fn () => $this->billService->annulBill($bill, 'Motivo', $this->user->id))
        ->toThrow(ValidationException::class);
});

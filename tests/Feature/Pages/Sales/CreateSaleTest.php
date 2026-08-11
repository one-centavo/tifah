<?php

use App\Models\Bill;
use App\Models\Category;
use App\Models\ConcentrationUnit;
use App\Models\Container;
use App\Models\ContentUnit;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Laboratory;
use App\Models\Lot;
use App\Models\Medicine;
use App\Models\MedicineBarcode;
use App\Models\SanitaryRegistry;
use App\Models\User;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->user = User::factory()->create(['role' => 'Auxiliar de Bodega']);
    $this->actingAs($this->user);
});

test('guest users are redirected to login page from sales create', function () {
    auth()->logout();

    $this->get(route('sales.create'))
        ->assertRedirect(route('login'));
});

test('authorized users can access sales create page', function () {
    $this->get(route('sales.create'))
        ->assertOk()
        ->assertSee('Proceso de Venta y Facturación')
        ->assertSee('Seleccionar Cliente');
});

test('it can search and select a customer', function () {
    $customer = Customer::factory()->create([
        'name' => 'Droguería San Jorge',
        'nit' => '900111222',
        'dv' => 1,
    ]);

    Volt::test('sales.create')
        ->set('customerSearch', 'San Jorge')
        ->assertSee('Droguería San Jorge')
        ->call('selectCustomer', $customer->id)
        ->assertSet('id_customer', $customer->id)
        ->assertSee('Droguería San Jorge')
        ->assertSee('NIT 900111222-1');
});

test('it identifies a medicine by barcode and opens FEFO lot allocation modal', function () {
    $medicine = Medicine::factory()->create(['name' => 'Ibuprofeno 800mg', 'selling_price' => 12000]);
    MedicineBarcode::factory()->create([
        'medicine_id' => $medicine->id,
        'barcode' => '7701234567890',
    ]);

    $lot = Lot::factory()->create([
        'medicine_id' => $medicine->id,
        'batch_number' => 'IBU-LOT-1',
        'expiration_date' => now()->addDays(40)->toDateString(),
        'current_quantity' => 10,
        'status' => 'active',
    ]);

    Volt::test('sales.create')
        ->set('productQuery', '7701234567890')
        ->call('handleProductScan')
        ->assertSet('showLotModal', true)
        ->assertSet('selectedMedicineId', $medicine->id)
        ->assertSee('Ibuprofeno 800mg')
        ->assertSee('IBU-LOT-1');
});

test('it shows error message when scanned medicine does not exist', function () {
    Volt::test('sales.create')
        ->set('productQuery', '999999999999')
        ->call('handleProductScan')
        ->assertSet('productSearchError', 'El medicamento no se encuentra registrado en el sistema.')
        ->assertSee('+ Registrar Medicamento');
});

test('it allocates quantities according to FEFO and allows locking a lot', function () {
    $medicine = Medicine::factory()->create(['selling_price' => 5000]);

    $lot1 = Lot::factory()->create([
        'medicine_id' => $medicine->id,
        'batch_number' => 'LOT-SOON',
        'expiration_date' => now()->addDays(15)->toDateString(),
        'current_quantity' => 5,
        'status' => 'active',
    ]);

    $lot2 = Lot::factory()->create([
        'medicine_id' => $medicine->id,
        'batch_number' => 'LOT-LATER',
        'expiration_date' => now()->addDays(90)->toDateString(),
        'current_quantity' => 20,
        'status' => 'active',
    ]);

    $component = Volt::test('sales.create')
        ->call('openLotModal', $medicine->id)
        ->set('requestedMedicineQuantity', 8)
        ->assertSee('LOT-SOON')
        ->assertSee('LOT-LATER');

    // Lock lot2 with 6 units manually
    $component->call('toggleLotLock', $lot2->id)
        ->call('updateLockedLotQuantity', $lot2->id, 6)
        ->call('addAllocationsToCart')
        ->assertSet('showLotModal', false);

    // Cart should contain items
    expect($component->get('cart'))->not->toBeEmpty();
});

test('it successfully completes a sale and updates inventory', function () {
    $customer = Customer::factory()->create();
    $medicine = Medicine::factory()->create(['name' => 'Amoxicilina 500mg', 'selling_price' => 8000]);

    $lot = Lot::factory()->create([
        'medicine_id' => $medicine->id,
        'batch_number' => 'AMX-001',
        'current_quantity' => 30,
        'status' => 'active',
    ]);

    Volt::test('sales.create')
        ->call('selectCustomer', $customer->id)
        ->call('openLotModal', $medicine->id)
        ->set('requestedMedicineQuantity', 10)
        ->call('addAllocationsToCart')
        ->set('payment_method', 'cash')
        ->call('finalizeSale')
        ->assertHasNoErrors()
        ->assertSet('showSuccessModal', true);

    $lot->refresh();
    expect($lot->current_quantity)->toBe(20);

    $bill = Bill::where('id_customer', $customer->id)->latest('id')->first();
    expect($bill)->not->toBeNull();
    expect($bill->status)->toBe('active');
    expect((float) $bill->total_amount)->toBe(80000.0);

    $movement = InventoryMovement::where('reference_id', $bill->id)->first();
    expect($movement)->not->toBeNull();
    expect($movement->type)->toBe('exit');
    expect($movement->quantity)->toBe(10);
});

test('it validates credit sales against payment due date and customer credit limit', function () {
    $customer = Customer::factory()->create([
        'credit_limit' => 20000,
    ]);

    $medicine = Medicine::factory()->create(['selling_price' => 50000]);
    $lot = Lot::factory()->create([
        'medicine_id' => $medicine->id,
        'current_quantity' => 10,
        'status' => 'active',
    ]);

    Volt::test('sales.create')
        ->call('selectCustomer', $customer->id)
        ->call('openLotModal', $medicine->id)
        ->set('requestedMedicineQuantity', 1)
        ->call('addAllocationsToCart')
        ->set('payment_method', 'credit')
        ->set('payment_due_date', '')
        ->call('finalizeSale')
        ->assertHasErrors(['payment_due_date']);

    // Now with due date but exceeding credit limit (50,000 > 20,000)
    Volt::test('sales.create')
        ->call('selectCustomer', $customer->id)
        ->call('openLotModal', $medicine->id)
        ->set('requestedMedicineQuantity', 1)
        ->call('addAllocationsToCart')
        ->set('payment_method', 'credit')
        ->set('payment_due_date', now()->addDays(15)->toDateString())
        ->call('finalizeSale')
        ->assertHasErrors(['payment_method']);
});

test('it allows registering a new medicine on-the-fly without losing sales screen state', function () {
    $category = Category::factory()->create();
    $laboratory = Laboratory::factory()->create();
    $sanitaryRegistry = SanitaryRegistry::factory()->create();
    $container = Container::factory()->create();
    $contentUnit = ContentUnit::factory()->create();
    $concentrationUnit = ConcentrationUnit::factory()->create();

    Volt::test('sales.create')
        ->call('openQuickMedicineModal')
        ->set('quickName', 'Loratadina Jarabe')
        ->set('quickSellingPrice', 9500)
        ->set('quickCategoryId', $category->id)
        ->set('quickLaboratoryId', $laboratory->id)
        ->set('quickSanitaryRegistryId', $sanitaryRegistry->id)
        ->set('quickContainerId', $container->id)
        ->set('quickContentUnitId', $contentUnit->id)
        ->set('quickConcentrationUnitId', $concentrationUnit->id)
        ->set('quickConcentrationValue', 5)
        ->set('quickContentQuantity', 1)
        ->call('saveQuickMedicine')
        ->assertSet('showQuickMedicineModal', false);

    expect(Medicine::where('name', 'Loratadina Jarabe')->exists())->toBeTrue();
});


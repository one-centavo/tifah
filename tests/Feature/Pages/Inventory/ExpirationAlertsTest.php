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
use Carbon\Carbon;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->user = User::factory()->create([
        'role' => 'admin',
        'first_name' => 'John',
        'last_name' => 'Doe',
    ]);

    $this->category = Category::factory()->create();
    $this->laboratory = Laboratory::factory()->create();
    $this->sanitaryRegistry = SanitaryRegistry::factory()->create(['laboratory_id' => $this->laboratory->id]);
    $this->container = Container::factory()->create();
    $this->contentUnit = ContentUnit::factory()->create();
    $this->concentrationUnit = ConcentrationUnit::factory()->create();

    $this->medicineA = Medicine::factory()->create([
        'category_id' => $this->category->id,
        'laboratory_id' => $this->laboratory->id,
        'sanitary_registry_id' => $this->sanitaryRegistry->id,
        'container_id' => $this->container->id,
        'content_unit_id' => $this->contentUnit->id,
        'concentration_unit_id' => $this->concentrationUnit->id,
        'name' => 'Amoxicilina 500mg Capsulas',
        'generic_name' => 'Amoxicilina',
    ]);

    $this->medicineB = Medicine::factory()->create([
        'category_id' => $this->category->id,
        'laboratory_id' => $this->laboratory->id,
        'sanitary_registry_id' => $this->sanitaryRegistry->id,
        'container_id' => $this->container->id,
        'content_unit_id' => $this->contentUnit->id,
        'concentration_unit_id' => $this->concentrationUnit->id,
        'name' => 'Losartan 50mg Tabletas',
        'generic_name' => 'Losartan Potasico',
    ]);

    $this->supplier = Supplier::factory()->create();
    $this->purchaseOrder = PurchaseOrder::factory()->create([
        'supplier_id' => $this->supplier->id,
        'status' => 'received',
    ]);
});

test('guest users are redirected to login page from expiration alerts and pdf', function () {
    $this->get(route('inventory.expiration-alerts'))
        ->assertRedirect(route('login'));

    $this->get(route('inventory.expiration-alerts.pdf'))
        ->assertRedirect(route('login'));
});

test('authorized users can access expiration alerts dashboard', function () {
    $this->actingAs($this->user);

    $this->get(route('inventory.expiration-alerts'))
        ->assertOk()
        ->assertSeeVolt('inventory.expiration-alerts')
        ->assertSee('Control y Gestión de Alertas de Vencimiento')
        ->assertSee('Total en Alerta');
});

test('it displays expiring lots categorized by color code with correct days countdown and monetary risk', function () {
    $this->actingAs($this->user);

    // Critical (Red): 15 days
    Lot::factory()->create([
        'medicine_id' => $this->medicineA->id,
        'purchase_order_id' => $this->purchaseOrder->id,
        'batch_number' => 'LOT-RED-001',
        'expiration_date' => Carbon::today()->addDays(15)->toDateString(),
        'current_quantity' => 10,
        'unit_purchase_price' => 2000.00,
        'status' => 'active',
    ]);

    // Warning (Orange): 45 days
    Lot::factory()->create([
        'medicine_id' => $this->medicineB->id,
        'purchase_order_id' => $this->purchaseOrder->id,
        'batch_number' => 'LOT-ORANGE-002',
        'expiration_date' => Carbon::today()->addDays(45)->toDateString(),
        'current_quantity' => 25,
        'unit_purchase_price' => 1000.00,
        'status' => 'active',
    ]);

    Volt::test('inventory.expiration-alerts')
        ->assertSee('LOT-RED-001')
        ->assertSee('Amoxicilina 500mg Capsulas')
        ->assertSee('15 días')
        ->assertSee('≤ 30d · Rojo')
        ->assertSee('$20.000')
        ->assertSee('LOT-ORANGE-002')
        ->assertSee('Losartan 50mg Tabletas')
        ->assertSee('45 días')
        ->assertSee('31-60d · Naranja')
        ->assertSee('$25.000')
        ->assertSee('$45.000'); // Total monetary risk
});

test('it can filter expiring lots by urgency tier', function () {
    $this->actingAs($this->user);

    Lot::factory()->create([
        'medicine_id' => $this->medicineA->id,
        'purchase_order_id' => $this->purchaseOrder->id,
        'batch_number' => 'LOT-CRIT-11',
        'expiration_date' => Carbon::today()->addDays(20)->toDateString(),
        'current_quantity' => 10,
        'status' => 'active',
    ]);

    Lot::factory()->create([
        'medicine_id' => $this->medicineB->id,
        'purchase_order_id' => $this->purchaseOrder->id,
        'batch_number' => 'LOT-WARN-22',
        'expiration_date' => Carbon::today()->addDays(50)->toDateString(),
        'current_quantity' => 20,
        'status' => 'active',
    ]);

    Volt::test('inventory.expiration-alerts')
        ->call('setUrgencyFilter', 'critical')
        ->assertSee('LOT-CRIT-11')
        ->assertDontSee('LOT-WARN-22')
        ->call('setUrgencyFilter', 'warning')
        ->assertSee('LOT-WARN-22')
        ->assertDontSee('LOT-CRIT-11')
        ->call('setUrgencyFilter', '')
        ->assertSee('LOT-CRIT-11')
        ->assertSee('LOT-WARN-22');
});

test('it can search expiring lots by medicine name and barcode', function () {
    $this->actingAs($this->user);

    MedicineBarcode::factory()->create([
        'medicine_id' => $this->medicineB->id,
        'barcode' => '7701122334455',
    ]);

    Lot::factory()->create([
        'medicine_id' => $this->medicineA->id,
        'purchase_order_id' => $this->purchaseOrder->id,
        'batch_number' => 'LOT-AMOX-SEARCH',
        'expiration_date' => Carbon::today()->addDays(25)->toDateString(),
        'current_quantity' => 10,
        'status' => 'active',
    ]);

    Lot::factory()->create([
        'medicine_id' => $this->medicineB->id,
        'purchase_order_id' => $this->purchaseOrder->id,
        'batch_number' => 'LOT-LOSAR-SEARCH',
        'expiration_date' => Carbon::today()->addDays(30)->toDateString(),
        'current_quantity' => 15,
        'status' => 'active',
    ]);

    Volt::test('inventory.expiration-alerts')
        ->set('search', 'Amoxicilina')
        ->assertSee('LOT-AMOX-SEARCH')
        ->assertDontSee('LOT-LOSAR-SEARCH')
        ->set('search', '7701122334455')
        ->assertSee('LOT-LOSAR-SEARCH')
        ->assertDontSee('LOT-AMOX-SEARCH');
});

test('it handles daily dismiss action and updates notification badge', function () {
    $this->actingAs($this->user);

    Lot::factory()->create([
        'medicine_id' => $this->medicineA->id,
        'purchase_order_id' => $this->purchaseOrder->id,
        'batch_number' => 'LOT-DISMISS-TEST',
        'expiration_date' => Carbon::today()->addDays(20)->toDateString(),
        'current_quantity' => 10,
        'status' => 'active',
    ]);

    // Test initial badge state: 1 unread alert
    Volt::test('components.expiration-notifications-badge')
        ->assertSee('1 total')
        ->assertSet('unreadCount', 1)
        ->assertSee('LOT-DISMISS-TEST');

    // Test main view dismiss action
    Volt::test('inventory.expiration-alerts')
        ->call('dismissToday')
        ->assertDispatched('expiration-alerts-updated')
        ->assertSee('Las notificaciones visuales del día han sido marcadas como leídas');

    // Test badge state after dismiss: 0 unread alerts
    Volt::test('components.expiration-notifications-badge')
        ->assertSet('unreadCount', 0)
        ->assertDontSee('bg-red-600 text-white text-[10px]');
});

test('it allows downloading the physical warehouse guide in PDF format', function () {
    $this->actingAs($this->user);

    Lot::factory()->create([
        'medicine_id' => $this->medicineA->id,
        'purchase_order_id' => $this->purchaseOrder->id,
        'batch_number' => 'LOT-GUIDE-001',
        'expiration_date' => Carbon::today()->addDays(18)->toDateString(),
        'current_quantity' => 100,
        'status' => 'active',
    ]);

    $response = $this->get(route('inventory.expiration-alerts.pdf'));

    $response
        ->assertOk()
        ->assertHeader('Content-Type', 'text/html; charset=UTF-8')
        ->assertSee('Guía de Retiro y Marcación')
        ->assertSee('LOT-GUIDE-001')
        ->assertSee('Amoxicilina 500mg Capsulas')
        ->assertSee('18d');
});

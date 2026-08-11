<?php

use App\Models\Bill;
use App\Models\BillDetail;
use App\Models\Category;
use App\Models\ConcentrationUnit;
use App\Models\Container;
use App\Models\ContentUnit;
use App\Models\Customer;
use App\Models\InventoryMovement;
use App\Models\Laboratory;
use App\Models\Lot;
use App\Models\Medicine;
use App\Models\PurchaseOrder;
use App\Models\SanitaryRegistry;
use App\Models\Supplier;
use App\Models\User;
use App\Services\DashboardService;
use Carbon\Carbon;

beforeEach(function () {
    $this->service = new DashboardService();
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('it calculates KPI metrics accurately', function () {
    $customer = Customer::factory()->create();
    $category = Category::factory()->create();
    $laboratory = Laboratory::factory()->create();
    $sanitaryRegistry = SanitaryRegistry::factory()->create(['laboratory_id' => $laboratory->id]);
    $container = Container::factory()->create();
    $contentUnit = ContentUnit::factory()->create();
    $concentrationUnit = ConcentrationUnit::factory()->create();

    $medicine = Medicine::factory()->create([
        'category_id' => $category->id,
        'laboratory_id' => $laboratory->id,
        'sanitary_registry_id' => $sanitaryRegistry->id,
        'container_id' => $container->id,
        'content_unit_id' => $contentUnit->id,
        'concentration_unit_id' => $concentrationUnit->id,
        'selling_price' => 20.00,
        'min_stock' => 10,
    ]);

    $supplier = Supplier::factory()->create();
    $receivedOrder = PurchaseOrder::factory()->create([
        'supplier_id' => $supplier->id,
        'status' => 'received',
    ]);

    $lot = Lot::factory()->create([
        'medicine_id' => $medicine->id,
        'purchase_order_id' => $receivedOrder->id,
        'current_quantity' => 5,
        'unit_purchase_price' => 10.00,
        'status' => 'active',
        'expiration_date' => Carbon::today()->addDays(15),
    ]);

    Bill::factory()->create([
        'id_customer' => $customer->id,
        'status' => 'active',
        'total_amount' => 150.00,
        'created_at' => Carbon::now(),
    ]);

    Bill::factory()->create([
        'id_customer' => $customer->id,
        'status' => 'annulled',
        'total_amount' => 500.00,
        'created_at' => Carbon::now(),
    ]);

    PurchaseOrder::factory()->create([
        'supplier_id' => $supplier->id,
        'status' => 'pending',
        'total_estimated' => 300.00,
    ]);

    $kpis = $this->service->getKpiMetrics('month');

    expect($kpis['period_sales'])->toBe(150.00)
        ->and($kpis['today_sales'])->toBe(150.00)
        ->and($kpis['inventory_cost_valuation'])->toBe(50.00)
        ->and($kpis['inventory_selling_valuation'])->toBe(100.00)
        ->and($kpis['critical_stock_medicines_count'])->toBe(1)
        ->and($kpis['expiring_lots_count'])->toBe(1)
        ->and($kpis['pending_purchase_orders_count'])->toBe(1)
        ->and($kpis['pending_orders_amount'])->toBe(300.00);
});

test('it returns critical alerts correctly', function () {
    $laboratory = Laboratory::factory()->create();
    $sanitaryRegistry = SanitaryRegistry::factory()->create([
        'laboratory_id' => $laboratory->id,
        'expiration_date' => Carbon::today()->addDays(20),
        'status' => 'valid',
    ]);
    $category = Category::factory()->create();
    $container = Container::factory()->create();
    $contentUnit = ContentUnit::factory()->create();
    $concentrationUnit = ConcentrationUnit::factory()->create();

    $medicine = Medicine::factory()->create([
        'category_id' => $category->id,
        'laboratory_id' => $laboratory->id,
        'sanitary_registry_id' => $sanitaryRegistry->id,
        'container_id' => $container->id,
        'content_unit_id' => $contentUnit->id,
        'concentration_unit_id' => $concentrationUnit->id,
        'min_stock' => 15,
    ]);

    $criticalLot = Lot::factory()->create([
        'medicine_id' => $medicine->id,
        'current_quantity' => 8,
        'expiration_date' => Carbon::today()->addDays(10),
        'status' => 'active',
    ]);

    $damagedLot = Lot::factory()->create([
        'medicine_id' => $medicine->id,
        'current_quantity' => 2,
        'expiration_date' => Carbon::today()->addDays(200),
        'status' => 'damaged',
    ]);

    $alerts = $this->service->getCriticalAlerts();

    expect($alerts['critical_lots'])->toHaveCount(1)
        ->and($alerts['critical_sanitary_registries'])->toHaveCount(1)
        ->and($alerts['low_stock_medicines'])->toHaveCount(1)
        ->and($alerts['quarantine_lots_count'])->toBe(1);
});

test('it categorizes FEFO distribution properly', function () {
    $medicine = Medicine::factory()->create();

    // Critical (<30 days)
    Lot::factory()->create([
        'medicine_id' => $medicine->id,
        'expiration_date' => Carbon::today()->addDays(15),
        'current_quantity' => 10,
        'status' => 'active',
    ]);

    // Warning (31 - 90 days)
    Lot::factory()->create([
        'medicine_id' => $medicine->id,
        'expiration_date' => Carbon::today()->addDays(45),
        'current_quantity' => 10,
        'status' => 'active',
    ]);

    // Optimal (>90 days)
    Lot::factory()->create([
        'medicine_id' => $medicine->id,
        'expiration_date' => Carbon::today()->addDays(180),
        'current_quantity' => 10,
        'status' => 'active',
    ]);

    $fefo = $this->service->getFefoDistribution();

    expect($fefo['critical'])->toBe(1)
        ->and($fefo['warning'])->toBe(1)
        ->and($fefo['optimal'])->toBe(1)
        ->and($fefo['total'])->toBe(3);
});

test('it formats sales and purchases chart data', function () {
    $chartData = $this->service->getSalesVsPurchasesChartData(7);

    expect($chartData['labels'])->toHaveCount(7)
        ->and($chartData['sales'])->toHaveCount(7)
        ->and($chartData['purchases'])->toHaveCount(7);
});

test('it summarizes special control and cold chain products', function () {
    Medicine::factory()->create([
        'is_cold_chain' => true,
        'is_special_control' => false,
    ]);

    Medicine::factory()->create([
        'is_cold_chain' => false,
        'is_special_control' => true,
    ]);

    $summary = $this->service->getSpecialControlSummary();

    expect($summary['cold_chain_medicines'])->toBe(1)
        ->and($summary['special_control_medicines'])->toBe(1);
});

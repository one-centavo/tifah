<?php

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
use App\Services\ExpirationAlertService;
use Carbon\Carbon;

beforeEach(function () {
    $this->service = new ExpirationAlertService;
    $this->user = User::factory()->create(['role' => 'admin']);
    $this->actingAs($this->user);

    $this->category = Category::factory()->create();
    $this->laboratory = Laboratory::factory()->create();
    $this->sanitaryRegistry = SanitaryRegistry::factory()->create(['laboratory_id' => $this->laboratory->id]);
    $this->container = Container::factory()->create();
    $this->contentUnit = ContentUnit::factory()->create();
    $this->concentrationUnit = ConcentrationUnit::factory()->create();

    $this->medicine = Medicine::factory()->create([
        'category_id' => $this->category->id,
        'laboratory_id' => $this->laboratory->id,
        'sanitary_registry_id' => $this->sanitaryRegistry->id,
        'container_id' => $this->container->id,
        'content_unit_id' => $this->contentUnit->id,
        'concentration_unit_id' => $this->concentrationUnit->id,
        'name' => 'Amoxicilina 500mg',
        'generic_name' => 'Amoxicilina',
    ]);

    $this->supplier = Supplier::factory()->create();
    $this->purchaseOrder = PurchaseOrder::factory()->create([
        'supplier_id' => $this->supplier->id,
        'status' => 'received',
    ]);
});

test('it correctly categorizes batches into 30, 60, and 90 day urgency tiers', function () {
    expect($this->service->getUrgencyTier(10))->toBe(ExpirationAlertService::TIER_CRITICAL)
        ->and($this->service->getUrgencyTier(30))->toBe(ExpirationAlertService::TIER_CRITICAL)
        ->and($this->service->getUrgencyTier(31))->toBe(ExpirationAlertService::TIER_WARNING)
        ->and($this->service->getUrgencyTier(60))->toBe(ExpirationAlertService::TIER_WARNING)
        ->and($this->service->getUrgencyTier(61))->toBe(ExpirationAlertService::TIER_ATTENTION)
        ->and($this->service->getUrgencyTier(90))->toBe(ExpirationAlertService::TIER_ATTENTION);
});

test('it queries expiring lots within 90 days and ignores non-alert batches', function () {
    // Critical lot (20 days)
    $criticalLot = Lot::factory()->create([
        'medicine_id' => $this->medicine->id,
        'purchase_order_id' => $this->purchaseOrder->id,
        'batch_number' => 'LOT-CRITICAL',
        'expiration_date' => Carbon::today()->addDays(20)->toDateString(),
        'current_quantity' => 50,
        'unit_purchase_price' => 1000.00,
        'status' => 'active',
    ]);

    // Warning lot (45 days)
    $warningLot = Lot::factory()->create([
        'medicine_id' => $this->medicine->id,
        'purchase_order_id' => $this->purchaseOrder->id,
        'batch_number' => 'LOT-WARNING',
        'expiration_date' => Carbon::today()->addDays(45)->toDateString(),
        'current_quantity' => 30,
        'unit_purchase_price' => 2000.00,
        'status' => 'active',
    ]);

    // Attention lot (80 days)
    $attentionLot = Lot::factory()->create([
        'medicine_id' => $this->medicine->id,
        'purchase_order_id' => $this->purchaseOrder->id,
        'batch_number' => 'LOT-ATTENTION',
        'expiration_date' => Carbon::today()->addDays(80)->toDateString(),
        'current_quantity' => 20,
        'unit_purchase_price' => 1500.00,
        'status' => 'active',
    ]);

    // Safe lot (> 90 days) - should be excluded
    Lot::factory()->create([
        'medicine_id' => $this->medicine->id,
        'purchase_order_id' => $this->purchaseOrder->id,
        'batch_number' => 'LOT-SAFE',
        'expiration_date' => Carbon::today()->addDays(120)->toDateString(),
        'current_quantity' => 100,
        'status' => 'active',
    ]);

    // Expired lot (< 0 days) - should be excluded
    Lot::factory()->create([
        'medicine_id' => $this->medicine->id,
        'purchase_order_id' => $this->purchaseOrder->id,
        'batch_number' => 'LOT-EXPIRED',
        'expiration_date' => Carbon::today()->subDays(5)->toDateString(),
        'current_quantity' => 10,
        'status' => 'active',
    ]);

    // Zero-stock lot - should be excluded
    Lot::factory()->create([
        'medicine_id' => $this->medicine->id,
        'purchase_order_id' => $this->purchaseOrder->id,
        'batch_number' => 'LOT-ZERO',
        'expiration_date' => Carbon::today()->addDays(15)->toDateString(),
        'current_quantity' => 0,
        'status' => 'active',
    ]);

    // Blocked lot - should be excluded
    Lot::factory()->create([
        'medicine_id' => $this->medicine->id,
        'purchase_order_id' => $this->purchaseOrder->id,
        'batch_number' => 'LOT-BLOCKED',
        'expiration_date' => Carbon::today()->addDays(15)->toDateString(),
        'current_quantity' => 25,
        'status' => 'blocked',
    ]);

    // Damaged lot - should be excluded
    Lot::factory()->create([
        'medicine_id' => $this->medicine->id,
        'purchase_order_id' => $this->purchaseOrder->id,
        'batch_number' => 'LOT-DAMAGED',
        'expiration_date' => Carbon::today()->addDays(15)->toDateString(),
        'current_quantity' => 25,
        'status' => 'damaged',
    ]);

    $lots = $this->service->getExpiringLotsQuery(90)->get();

    expect($lots)->toHaveCount(3)
        ->and($lots->pluck('batch_number')->toArray())->toEqual([
            'LOT-CRITICAL',
            'LOT-WARNING',
            'LOT-ATTENTION',
        ]);
});

test('it calculates alert summary metrics and financial risk with integer rounding', function () {
    Lot::factory()->create([
        'medicine_id' => $this->medicine->id,
        'purchase_order_id' => $this->purchaseOrder->id,
        'batch_number' => 'LOT-1',
        'expiration_date' => Carbon::today()->addDays(15)->toDateString(),
        'current_quantity' => 10,
        'unit_purchase_price' => 1250.50,
        'status' => 'active',
    ]);

    Lot::factory()->create([
        'medicine_id' => $this->medicine->id,
        'purchase_order_id' => $this->purchaseOrder->id,
        'batch_number' => 'LOT-2',
        'expiration_date' => Carbon::today()->addDays(40)->toDateString(),
        'current_quantity' => 20,
        'unit_purchase_price' => 3000.00,
        'status' => 'active',
    ]);

    $metrics = $this->service->getAlertSummaryMetrics();

    expect($metrics['total_lots'])->toBe(2)
        ->and($metrics['total_units'])->toBe(30)
        ->and($metrics['critical_count'])->toBe(1)
        ->and($metrics['critical_monetary_risk'])->toBe(12505) // 10 * 1250.50 = 12505
        ->and($metrics['warning_count'])->toBe(1)
        ->and($metrics['warning_monetary_risk'])->toBe(60000) // 20 * 3000 = 60000
        ->and($metrics['attention_count'])->toBe(0)
        ->and($metrics['total_monetary_risk'])->toBe(72505);
});

test('it handles daily dismissals per user and resets on subsequent days', function () {
    Lot::factory()->create([
        'medicine_id' => $this->medicine->id,
        'purchase_order_id' => $this->purchaseOrder->id,
        'expiration_date' => Carbon::today()->addDays(25)->toDateString(),
        'current_quantity' => 15,
        'status' => 'active',
    ]);

    $otherUser = User::factory()->create(['role' => 'warehouse_assistant']);

    // Initially unread for both users
    expect($this->service->isDismissedTodayForUser($this->user))->toBeFalse()
        ->and($this->service->getUnreadAlertCountForUser($this->user))->toBe(1)
        ->and($this->service->isDismissedTodayForUser($otherUser))->toBeFalse()
        ->and($this->service->getUnreadAlertCountForUser($otherUser))->toBe(1);

    // User dismisses today's alerts
    $this->service->dismissAlertsForToday($this->user);

    expect($this->service->isDismissedTodayForUser($this->user))->toBeTrue()
        ->and($this->service->getUnreadAlertCountForUser($this->user))->toBe(0)
        ->and($this->service->isDismissedTodayForUser($otherUser))->toBeFalse()
        ->and($this->service->getUnreadAlertCountForUser($otherUser))->toBe(1);

    // Simulate next day
    Carbon::setTestNow(Carbon::tomorrow());

    expect($this->service->isDismissedTodayForUser($this->user))->toBeFalse()
        ->and($this->service->getUnreadAlertCountForUser($this->user))->toBe(1);

    Carbon::setTestNow(); // Reset time
});

test('it filters expiring lots by search query', function () {
    $secondMedicine = Medicine::factory()->create([
        'category_id' => $this->category->id,
        'laboratory_id' => $this->laboratory->id,
        'sanitary_registry_id' => $this->sanitaryRegistry->id,
        'container_id' => $this->container->id,
        'content_unit_id' => $this->contentUnit->id,
        'concentration_unit_id' => $this->concentrationUnit->id,
        'name' => 'Ibuprofeno 400mg',
        'generic_name' => 'Ibuprofeno',
    ]);

    MedicineBarcode::factory()->create([
        'medicine_id' => $secondMedicine->id,
        'barcode' => '7709998887776',
    ]);

    Lot::factory()->create([
        'medicine_id' => $this->medicine->id,
        'purchase_order_id' => $this->purchaseOrder->id,
        'batch_number' => 'BATCH-AMOX-01',
        'expiration_date' => Carbon::today()->addDays(20)->toDateString(),
        'current_quantity' => 10,
        'status' => 'active',
    ]);

    Lot::factory()->create([
        'medicine_id' => $secondMedicine->id,
        'purchase_order_id' => $this->purchaseOrder->id,
        'batch_number' => 'BATCH-IBUP-02',
        'expiration_date' => Carbon::today()->addDays(25)->toDateString(),
        'current_quantity' => 20,
        'status' => 'active',
    ]);

    $searchByName = $this->service->getExpiringLotsQuery(90, null, 'Amoxicilina')->get();
    expect($searchByName)->toHaveCount(1)
        ->and($searchByName->first()->batch_number)->toBe('BATCH-AMOX-01');

    $searchByBatch = $this->service->getExpiringLotsQuery(90, null, 'IBUP-02')->get();
    expect($searchByBatch)->toHaveCount(1)
        ->and($searchByBatch->first()->batch_number)->toBe('BATCH-IBUP-02');

    $searchByBarcode = $this->service->getExpiringLotsQuery(90, null, '7709998887776')->get();
    expect($searchByBarcode)->toHaveCount(1)
        ->and($searchByBarcode->first()->batch_number)->toBe('BATCH-IBUP-02');
});

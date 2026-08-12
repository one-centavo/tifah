<?php

use App\Mail\WeeklyExpirationReportMail;
use App\Models\Category;
use App\Models\ConcentrationUnit;
use App\Models\Container;
use App\Models\ContentUnit;
use App\Models\Laboratory;
use App\Models\Lot;
use App\Models\Medicine;
use App\Models\PurchaseOrder;
use App\Models\SanitaryRegistry;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => 'admin',
        'email' => 'admin@tifah.test',
        'first_name' => 'Admin',
        'last_name' => 'User',
    ]);

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
        'name' => 'Acetaminofen 500mg',
        'generic_name' => 'Acetaminofen',
    ]);

    $this->supplier = Supplier::factory()->create();
    $this->purchaseOrder = PurchaseOrder::factory()->create([
        'supplier_id' => $this->supplier->id,
        'status' => 'received',
    ]);

    Lot::factory()->create([
        'medicine_id' => $this->medicine->id,
        'purchase_order_id' => $this->purchaseOrder->id,
        'batch_number' => 'LOT-CRIT-99',
        'expiration_date' => Carbon::today()->addDays(15)->toDateString(),
        'current_quantity' => 40,
        'unit_purchase_price' => 2500.00,
        'status' => 'active',
    ]);
});

test('it executes app:scan-expiration-alerts command successfully', function () {
    $this->artisan('app:scan-expiration-alerts')
        ->expectsOutput('Starting daily expiration alerts scan...')
        ->expectsOutput('Daily expiration scan completed successfully.')
        ->assertSuccessful();
});

test('it executes app:send-weekly-expiration-report and dispatches email to administrators', function () {
    Mail::fake();

    $anotherAdmin = User::factory()->create([
        'role' => 'admin',
        'email' => 'finance@tifah.test',
    ]);

    $warehouseUser = User::factory()->create([
        'role' => 'warehouse_assistant',
        'email' => 'bodega@tifah.test',
    ]);

    $this->artisan('app:send-weekly-expiration-report')
        ->expectsOutput('Generating weekly expiration alerts report...')
        ->expectsOutput('Weekly expiration report sent successfully to 2 administrator(s).')
        ->assertSuccessful();

    Mail::assertSent(WeeklyExpirationReportMail::class, function ($mail) {
        return $mail->hasTo('admin@tifah.test');
    });

    Mail::assertSent(WeeklyExpirationReportMail::class, function ($mail) {
        return $mail->hasTo('finance@tifah.test');
    });

    Mail::assertNotSent(WeeklyExpirationReportMail::class, function ($mail) {
        return $mail->hasTo('bodega@tifah.test');
    });
});

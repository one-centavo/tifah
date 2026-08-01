<?php

declare(strict_types=1);

use App\Models\Category;
use App\Models\Medicine;
use App\Models\MedicineBarcode;
use App\Models\User;
use Livewire\Volt\Volt;

test('guest users are redirected to login page from index', function () {
    $response = $this->get(route('medicines.index'));

    $response->assertRedirect('/login');
});

test('authorized users can access medicines management index page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('medicines.index'));

    $response
        ->assertOk()
        ->assertSeeVolt('medicines.index')
        ->assertSee('Catálogo de Medicamentos');
});

test('it displays the list of active medicines', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $medicine = Medicine::factory()->create(['name' => 'Lantus Insulin']);
    $barcode = MedicineBarcode::factory()->create([
        'medicine_id' => $medicine->id,
        'barcode' => '7701234567890',
        'is_main' => true,
    ]);

    Volt::test('medicines.index')
        ->assertSee('Lantus Insulin')
        ->assertSee('7701234567890');
});

test('it can search medicines by commercial name, generic name, or barcode', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $med1 = Medicine::factory()->create(['name' => 'Amoxicilina MK', 'generic_name' => 'Amoxicilina']);
    $bar1 = MedicineBarcode::factory()->create(['medicine_id' => $med1->id, 'barcode' => '1111111111']);

    $med2 = Medicine::factory()->create(['name' => 'Ibuprofeno Genfar', 'generic_name' => 'Ibuprofeno']);
    $bar2 = MedicineBarcode::factory()->create(['medicine_id' => $med2->id, 'barcode' => '2222222222']);

    // Search by name
    Volt::test('medicines.index')
        ->set('search', 'Amoxicilina')
        ->assertSee('Amoxicilina MK')
        ->assertDontSee('Ibuprofeno Genfar');

    // Search by barcode
    Volt::test('medicines.index')
        ->set('search', '2222222222')
        ->assertSee('Ibuprofeno Genfar')
        ->assertDontSee('Amoxicilina MK');
});

test('it can filter medicines by category', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $cat1 = Category::factory()->create(['name' => 'Antibióticos']);
    $med1 = Medicine::factory()->create(['name' => 'Amoxicilina MK', 'category_id' => $cat1->id]);

    $cat2 = Category::factory()->create(['name' => 'Analgésicos']);
    $med2 = Medicine::factory()->create(['name' => 'Ibuprofeno Genfar', 'category_id' => $cat2->id]);

    Volt::test('medicines.index')
        ->set('categoryFilter', (string) $cat1->id)
        ->assertSee('Amoxicilina MK')
        ->assertDontSee('Ibuprofeno Genfar');
});

test('it can soft delete a medicine and record deleter and delete related barcodes', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $medicine = Medicine::factory()->create(['name' => 'Rivotril']);
    $barcode = MedicineBarcode::factory()->create([
        'medicine_id' => $medicine->id,
        'barcode' => '3333333333',
        'is_main' => true,
    ]);

    $component = Volt::test('medicines.index')
        ->call('confirmMedicineDeletion', $medicine->id);

    expect($component->get('medicineIdBeingDeleted'))->toBe($medicine->id)
        ->and($component->get('medicineNameBeingDeleted'))->toBe('Rivotril');

    $component->call('deleteMedicine')
        ->assertHasNoErrors();

    // Medicine should be soft deleted and record deleter
    $medicine->refresh();
    expect($medicine->trashed())->toBeTrue()
        ->and($medicine->deleted_by)->toBe($user->id);

    // Barcode should also be soft deleted and record deleter
    $barcode->refresh();
    expect($barcode->trashed())->toBeTrue()
        ->and($barcode->deleted_by)->toBe($user->id);

    // Should no longer see it in active list
    Volt::test('medicines.index')
        ->assertDontSee('Rivotril');

    // Should see it when filtering by archived
    Volt::test('medicines.index')
        ->set('softDeleteFilter', 'archived')
        ->assertSee('Rivotril');
});

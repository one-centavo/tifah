<?php

use App\Http\Controllers\BillPdfController;
use App\Http\Controllers\ExpirationAlertPdfController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

Route::redirect('/', 'login');

Volt::route('dashboard', 'dashboard.index')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth'])->group(function () {
    Volt::route('categories', 'categories.index')->name('categories.index');
    Volt::route('categories/create', 'categories.create')->name('categories.create');
    Volt::route('categories/{category}/edit', 'categories.edit')->name('categories.edit');

    Volt::route('laboratories', 'laboratories.index')->name('laboratories.index');
    Volt::route('laboratories/create', 'laboratories.create')->name('laboratories.create');
    Volt::route('laboratories/{laboratory}/edit', 'laboratories.edit')->name('laboratories.edit');

    Volt::route('sanitary-registries', 'sanitary-registries.index')->name('sanitary-registries.index');
    Volt::route('sanitary-registries/create', 'sanitary-registries.create')->name('sanitary-registries.create');
    Volt::route('sanitary-registries/{sanitary_registry}/edit', 'sanitary-registries.edit')->name('sanitary-registries.edit');

    Volt::route('suppliers', 'suppliers.index')->name('suppliers.index');
    Volt::route('suppliers/create', 'suppliers.create')->name('suppliers.create');
    Volt::route('suppliers/{supplier}/edit', 'suppliers.edit')->name('suppliers.edit');

    Volt::route('customers', 'customers.index')->name('customers.index');
    Volt::route('customers/create', 'customers.create')->name('customers.create');
    Volt::route('customers/{customer}/edit', 'customers.edit')->name('customers.edit');

    Volt::route('medicines', 'medicines.index')->name('medicines.index');
    Volt::route('medicines/create', 'medicines.create')->name('medicines.create');
    Volt::route('medicines/{medicine}/edit', 'medicines.edit')->name('medicines.edit');

    Volt::route('inventory', 'inventory.index')->name('inventory.index');
    Volt::route('inventory/expiration-alerts', 'inventory.expiration-alerts')->name('inventory.expiration-alerts');
    Route::get('inventory/expiration-alerts/pdf', [ExpirationAlertPdfController::class, 'download'])->name('inventory.expiration-alerts.pdf');
    Volt::route('inventory/medicines/{medicine}/lots', 'inventory.medicine-lots')->name('inventory.medicine-lots');
    Volt::route('inventory/lots/{lot}/logs', 'inventory.lot-logs')->name('inventory.lots.logs');

    Volt::route('sales/create', 'sales.create')->name('sales.create');
    Volt::route('bills', 'bills.index')->name('bills.index');
    Route::get('bills/{bill}/pdf', [BillPdfController::class, 'download'])->name('bills.pdf');
});

require __DIR__.'/auth.php';

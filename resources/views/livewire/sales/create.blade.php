<?php

declare(strict_types=1);

use App\Models\Category;

use App\Models\ConcentrationUnit;
use App\Models\Container;
use App\Models\ContentUnit;
use App\Models\Customer;
use App\Models\Laboratory;
use App\Models\Lot;
use App\Models\Medicine;
use App\Models\MedicineBarcode;
use App\Models\SanitaryRegistry;
use App\Services\BillService;
use App\Services\MedicineService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    // Customer Selection State
    public ?int $id_customer = null;
    public string $customerSearch = '';
    public bool $showCustomerDropdown = false;

    // Product Search & Barcode Scan State
    public string $productQuery = '';
    public bool $showProductDropdown = false;
    public string $productSearchError = '';

    // Selected Medicine & Lot Modal State
    public ?int $selectedMedicineId = null;
    public int $requestedMedicineQuantity = 1;
    public array $fefoAllocations = [];
    public array $lockedLots = []; // [lot_id => quantity]
    public bool $showLotModal = false;

    // Cart Items State: array of items
    // Each item: [id, medicine_id, medicine_name, lot_id, batch_number, expiration_date, quantity, unit_price, subtotal]
    public array $cart = [];

    // Payment State
    public string $payment_method = 'cash';
    public ?string $payment_due_date = null;

    // Post-Sale Success Modal State
    public ?int $createdBillId = null;
    public string $createdInvoiceNumber = '';
    public bool $showSuccessModal = false;

    // Quick Medicine Registration Modal State
    public bool $showQuickMedicineModal = false;
    public string $quickName = '';
    public string $quickGenericName = '';
    public string $quickBarcode = '';
    public ?int $quickCategoryId = null;
    public ?int $quickLaboratoryId = null;
    public ?int $quickSanitaryRegistryId = null;
    public ?int $quickContainerId = null;
    public ?int $quickContentUnitId = null;
    public ?int $quickConcentrationUnitId = null;
    public ?float $quickConcentrationValue = null;
    public ?int $quickContentQuantity = null;
    public ?float $quickSellingPrice = null;

    public function mount(): void
    {
        $this->payment_due_date = now()->addDays(30)->toDateString();
    }

    /**
     * Search customers reactively.
     */
    public function getCustomersProperty()
    {
        if (strlen(trim($this->customerSearch)) < 1) {
            return Customer::where('is_active', true)->orderBy('name')->take(10)->get();
        }

        return Customer::where('is_active', true)
            ->where(function ($q) {
                $q->where('name', 'like', '%' . $this->customerSearch . '%')
                    ->orWhere('nit', 'like', '%' . $this->customerSearch . '%');
            })
            ->orderBy('name')
            ->take(15)
            ->get();
    }

    public function selectCustomer(int $customerId): void
    {
        $this->id_customer = $customerId;
        $this->customerSearch = '';
        $this->showCustomerDropdown = false;
    }

    public function clearCustomer(): void
    {
        $this->id_customer = null;
        $this->customerSearch = '';
    }

    public function getSelectedCustomerProperty(): ?Customer
    {
        return $this->id_customer ? Customer::find($this->id_customer) : null;
    }

    /**
     * Search medicines reactively.
     */
    public function getFoundMedicinesProperty()
    {
        if (strlen(trim($this->productQuery)) < 2) {
            return collect();
        }

        return Medicine::where(function ($q) {
            $q->where('name', 'like', '%' . $this->productQuery . '%')
                ->orWhere('generic_name', 'like', '%' . $this->productQuery . '%')
                ->orWhereHas('barcodes', function ($b) {
                    $b->where('barcode', 'like', '%' . $this->productQuery . '%');
                });
        })
            ->with(['container', 'contentUnit', 'lots' => function ($l) {
                $l->where('status', 'active')
                    ->where('current_quantity', '>', 0)
                    ->whereDate('expiration_date', '>=', now()->toDateString())
                    ->orderBy('expiration_date', 'asc');
            }])
            ->take(10)
            ->get();
    }

    /**
     * Handle direct Barcode Scanner / Enter key trigger.
     */
    public function handleProductScan(): void
    {
        $this->productSearchError = '';
        $query = trim($this->productQuery);

        if (empty($query)) {
            return;
        }

        // Try exact match on barcode first
        $barcode = MedicineBarcode::where('barcode', $query)->first();
        if ($barcode) {
            $this->openLotModal($barcode->medicine_id);
            $this->productQuery = '';
            $this->showProductDropdown = false;
            return;
        }

        // Try exact match on medicine name or ID
        $medicine = Medicine::where('name', $query)
            ->orWhere('generic_name', $query)
            ->first();

        if ($medicine) {
            $this->openLotModal($medicine->id);
            $this->productQuery = '';
            $this->showProductDropdown = false;
            return;
        }

        // If nothing found, show error and option to quick register
        $this->productSearchError = 'El medicamento no se encuentra registrado en el sistema.';
        $this->quickBarcode = $query;
    }

    /**
     * Open lot selector modal for a specific medicine.
     */
    public function openLotModal(int $medicineId): void
    {
        $this->selectedMedicineId = $medicineId;
        $this->requestedMedicineQuantity = 1;
        $this->lockedLots = [];
        $this->productSearchError = '';
        $this->showProductDropdown = false;

        $this->calculateLots();
        $this->showLotModal = true;
    }

    public function closeLotModal(): void
    {
        $this->showLotModal = false;
        $this->selectedMedicineId = null;
        $this->fefoAllocations = [];
        $this->lockedLots = [];
    }

    public function getSelectedMedicineProperty(): ?Medicine
    {
        return $this->selectedMedicineId ? Medicine::with(['lots', 'container', 'contentUnit'])->find($this->selectedMedicineId) : null;
    }

    /**
     * Recalculate FEFO allocations based on requested quantity and locked lots.
     */
    public function calculateLots(): void
    {
        if (! $this->selectedMedicine) {
            return;
        }

        $service = app(BillService::class);
        $result = $service->allocateFefoLots(
            $this->selectedMedicine,
            max(1, $this->requestedMedicineQuantity),
            $this->lockedLots
        );

        $this->fefoAllocations = $result['allocations'];
    }

    public function updatedRequestedMedicineQuantity(): void
    {
        $this->calculateLots();
    }

    public function toggleLotLock(int $lotId): void
    {
        if (isset($this->lockedLots[$lotId])) {
            unset($this->lockedLots[$lotId]);
        } else {
            // Find current allocated quantity for this lot
            $currentAlloc = collect($this->fefoAllocations)->firstWhere('lot_id', $lotId);
            $this->lockedLots[$lotId] = $currentAlloc['allocated_quantity'] ?? 1;
        }

        $this->calculateLots();
    }

    public function updateLockedLotQuantity(int $lotId, int $quantity): void
    {
        $lot = Lot::find($lotId);
        if ($lot) {
            $clamped = max(0, min($quantity, $lot->current_quantity));
            $this->lockedLots[$lotId] = $clamped;
            $this->calculateLots();
        }
    }

    /**
     * Add allocated lots to the main cart.
     */
    public function addAllocationsToCart(): void
    {
        if (empty($this->fefoAllocations)) {
            return;
        }

        $medicine = $this->selectedMedicine;
        if (! $medicine) {
            return;
        }

        foreach ($this->fefoAllocations as $alloc) {
            $qty = (int) $alloc['allocated_quantity'];
            if ($qty <= 0) {
                continue;
            }

            $lotId = (int) $alloc['lot_id'];
            $cartKey = $medicine->id . '_' . $lotId;

            $unitPrice = (float) ($alloc['unit_price'] ?? $medicine->selling_price ?? 0);

            // If already in cart, update quantity (up to lot current_quantity)
            if (isset($this->cart[$cartKey])) {
                $newQty = min($this->cart[$cartKey]['quantity'] + $qty, (int) $alloc['current_quantity']);
                $this->cart[$cartKey]['quantity'] = $newQty;
                $this->cart[$cartKey]['subtotal'] = round($newQty * $this->cart[$cartKey]['unit_price'], 0);
            } else {
                $this->cart[$cartKey] = [
                    'key' => $cartKey,
                    'medicine_id' => $medicine->id,
                    'medicine_name' => $medicine->name . ' (' . $medicine->presentation . ')',
                    'lot_id' => $lotId,
                    'batch_number' => $alloc['batch_number'],
                    'expiration_date' => $alloc['expiration_date'],
                    'current_quantity' => $alloc['current_quantity'],
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal' => round($qty * $unitPrice, 0),
                ];
            }
        }

        $this->closeLotModal();
    }

    /**
     * Remove an item from the cart.
     */
    public function removeCartItem(string $key): void
    {
        unset($this->cart[$key]);
    }

    /**
     * Update unit price on cart item (allowing special discounts).
     */
    public function updateCartItemPrice(string $key, float|int|string $newPrice): void
    {
        if (isset($this->cart[$key])) {
            $price = max(0, (float) $newPrice);
            $this->cart[$key]['unit_price'] = $price;
            $this->cart[$key]['subtotal'] = round($this->cart[$key]['quantity'] * $price, 0);
        }
    }

    /**
     * Update quantity on cart item.
     */
    public function updateCartItemQuantity(string $key, int|string $newQty): void
    {
        if (isset($this->cart[$key])) {
            $qty = max(1, min((int) $newQty, $this->cart[$key]['current_quantity']));
            $this->cart[$key]['quantity'] = $qty;
            $this->cart[$key]['subtotal'] = round($qty * $this->cart[$key]['unit_price'], 0);
        }
    }

    /**
     * Total invoice calculation rounded to nearest integer peso.
     */
    public function getInvoiceTotalProperty(): float
    {
        $sum = 0.0;
        foreach ($this->cart as $item) {
            $sum += (float) $item['subtotal'];
        }

        return round($sum, 0);
    }

    /**
     * Total items count in cart.
     */
    public function getCartUnitsCountProperty(): int
    {
        return array_sum(array_column($this->cart, 'quantity'));
    }

    /**
     * Open quick medicine registration modal.
     */
    public function openQuickMedicineModal(): void
    {
        $this->quickName = $this->productQuery;
        $this->quickGenericName = '';
        $this->quickSellingPrice = 0;
        $this->quickCategoryId = Category::first()?->id;
        $this->quickLaboratoryId = Laboratory::first()?->id;
        $this->quickSanitaryRegistryId = SanitaryRegistry::first()?->id;
        $this->quickContainerId = Container::first()?->id;
        $this->quickContentUnitId = ContentUnit::first()?->id;
        $this->quickConcentrationUnitId = ConcentrationUnit::first()?->id;
        $this->quickConcentrationValue = 100;
        $this->quickContentQuantity = 1;
        $this->showQuickMedicineModal = true;
    }

    public function closeQuickMedicineModal(): void
    {
        $this->showQuickMedicineModal = false;
    }

    /**
     * Save quick medicine on-the-fly.
     */
    public function saveQuickMedicine(MedicineService $medicineService): void
    {
        $validated = $this->validate([
            'quickName' => ['required', 'string', 'max:255'],
            'quickGenericName' => ['nullable', 'string', 'max:255'],
            'quickBarcode' => ['nullable', 'string', 'max:50'],
            'quickCategoryId' => ['required', 'exists:categories,id'],
            'quickLaboratoryId' => ['required', 'exists:laboratories,id'],
            'quickSanitaryRegistryId' => ['required', 'exists:sanitary_registries,id'],
            'quickContainerId' => ['required', 'exists:containers,id'],
            'quickContentUnitId' => ['required', 'exists:content_units,id'],
            'quickConcentrationUnitId' => ['required', 'exists:concentration_units,id'],
            'quickConcentrationValue' => ['required', 'numeric', 'min:0.01'],
            'quickContentQuantity' => ['required', 'integer', 'min:1'],
            'quickSellingPrice' => ['required', 'numeric', 'min:0'],
        ], [
            'quickName.required' => 'El nombre del medicamento es obligatorio.',
            'quickSellingPrice.required' => 'El precio de venta es obligatorio.',
        ]);

        $medicineData = [
            'name' => $this->quickName,
            'generic_name' => $this->quickGenericName ?: null,
            'barcode' => $this->quickBarcode ?: null,
            'category_id' => $this->quickCategoryId,
            'laboratory_id' => $this->quickLaboratoryId,
            'sanitary_registry_id' => $this->quickSanitaryRegistryId,
            'container_id' => $this->quickContainerId,
            'content_unit_id' => $this->quickContentUnitId,
            'concentration_unit_id' => $this->quickConcentrationUnitId,
            'concentration_value' => $this->quickConcentrationValue,
            'content_quantity' => $this->quickContentQuantity,
            'selling_price' => $this->quickSellingPrice,
            'min_stock' => 5,
            'created_by' => auth()->id(),
        ];

        $medicine = $medicineService->create($medicineData);

        $this->closeQuickMedicineModal();
        $this->productQuery = '';
        $this->openLotModal($medicine->id);
    }

    /**
     * Submit and finalize the sales transaction.
     */
    public function finalizeSale(BillService $billService): void
    {
        if (! $this->id_customer) {
            $this->addError('customer', 'Debe seleccionar un cliente antes de finalizar la venta.');
            return;
        }

        if (empty($this->cart)) {
            $this->addError('cart', 'Debe agregar al menos un medicamento con lote a la venta.');
            return;
        }

        if ($this->payment_method === 'credit' && empty($this->payment_due_date)) {
            $this->addError('payment_due_date', 'La fecha de vencimiento de pago es obligatoria para ventas a crédito.');
            return;
        }

        try {
            $billData = [
                'id_customer' => $this->id_customer,
                'payment_method' => $this->payment_method,
                'payment_due_date' => $this->payment_method === 'credit' ? $this->payment_due_date : null,
            ];

            $items = array_map(function ($item) {
                return [
                    'lot_id' => (int) $item['lot_id'],
                    'quantity' => (int) $item['quantity'],
                    'unit_price' => (float) $item['unit_price'],
                ];
            }, array_values($this->cart));

            $bill = $billService->createSale($billData, $items, auth()->id());

            $this->createdBillId = $bill->id;
            $this->createdInvoiceNumber = $bill->invoice_number;
            $this->showSuccessModal = true;

            // Clear state
            $this->cart = [];
            $this->id_customer = null;

        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }
        }
    }
}; ?>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <!-- Top Header -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                    Salida de Mercancía
                </span>
            </div>
            <h1 class="text-2xl font-bold text-blue-900 mt-1">Proceso de Venta y Facturación</h1>
            <p class="text-sm text-slate-600">
                Selecciona lotes con rotación FEFO, valida existencias en tiempo real y emite facturas de salida.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('bills.index') }}" wire:navigate
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors shadow-sm">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span>Historial de Facturas</span>
            </a>
        </div>
    </div>

    <!-- Main Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left Column: Customer & Product Scanner & Cart Table (Span 2) -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Card 1: Customer Selection -->
            <div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-base font-semibold text-blue-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <span>1. Seleccionar Cliente</span>
                    </h2>

                    @if($this->selectedCustomer)
                        <button type="button" wire:click="clearCustomer"
                            class="text-xs font-semibold text-red-600 hover:text-red-800 cursor-pointer">
                            Cambiar Cliente
                        </button>
                    @endif
                </div>

                @if(! $this->selectedCustomer)
                    <div class="relative" x-data="{ open: @entangle('showCustomerDropdown') }" @click.outside="open = false">
                        <div class="relative">
                            <input type="text"
                                wire:model.live.debounce.250ms="customerSearch"
                                @focus="open = true"
                                placeholder="Buscar cliente por NIT o Razón Social..."
                                class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-900 text-slate-800 placeholder-slate-400">
                            
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                        </div>

                        <!-- Dropdown Options -->
                        <div x-show="open"
                            class="absolute z-20 mt-1 w-full bg-white rounded-xl shadow-lg border border-slate-200 max-h-60 overflow-y-auto divide-y divide-slate-100">
                            @forelse($this->customers as $customer)
                                <button type="button"
                                    wire:click="selectCustomer({{ $customer->id }})"
                                    class="w-full text-left px-4 py-3 hover:bg-blue-50 transition flex items-center justify-between">
                                    <div>
                                        <div class="font-semibold text-sm text-slate-900">{{ $customer->name }}</div>
                                        <div class="text-xs text-slate-500">NIT: {{ $customer->nit }}-{{ $customer->dv }} &bull; {{ $customer->city }}</div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-xs px-2 py-0.5 rounded-full bg-green-100 text-green-800 font-medium">Activo</span>
                                    </div>
                                </button>
                            @empty
                                <div class="px-4 py-3 text-sm text-slate-500 text-center">
                                    No se encontraron clientes registrados con ese criterio.
                                </div>
                            @endforelse
                        </div>
                    </div>
                @else
                    <!-- Selected Customer Card -->
                    <div class="p-3.5 bg-blue-50/60 border border-blue-100 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-sm text-blue-950">{{ $this->selectedCustomer->name }}</span>
                                <span class="text-xs text-blue-700 font-mono bg-blue-100/80 px-2 py-0.5 rounded">
                                    NIT {{ $this->selectedCustomer->nit }}-{{ $this->selectedCustomer->dv }}
                                </span>
                            </div>
                            <div class="text-xs text-slate-600 mt-1">
                                {{ $this->selectedCustomer->address }} &bull; {{ $this->selectedCustomer->city }} &bull; Tel: {{ $this->selectedCustomer->phone_number }}
                            </div>
                        </div>

                        <div class="text-right border-t sm:border-t-0 pt-2 sm:pt-0 sm:border-l border-blue-200 sm:pl-4">
                            <div class="text-xs text-slate-500">Cupo Crédito</div>
                            <div class="font-bold text-sm text-slate-900">
                                ${{ number_format((float) $this->selectedCustomer->credit_limit, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>
                @endif

                @error('customer')
                    <div class="text-xs text-red-600 mt-2 font-medium">{{ $message }}</div>
                @enderror
            </div>

            <!-- Card 2: Product Barcode & Search Input -->
            <div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-base font-semibold text-blue-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                        </svg>
                        <span>2. Escaneo o Búsqueda de Medicamento</span>
                    </h2>
                </div>

                <div class="relative" x-data="{ dropdownOpen: @entangle('showProductDropdown') }" @click.outside="dropdownOpen = false">
                    <div class="flex gap-2">
                        <div class="relative flex-1">
                            <input type="text"
                                wire:model.live.debounce.250ms="productQuery"
                                wire:keydown.enter.prevent="handleProductScan"
                                @focus="dropdownOpen = true"
                                autofocus
                                placeholder="Escanear código de barras o escribir nombre del medicamento..."
                                class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-900 text-slate-800 placeholder-slate-400">
                            
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path>
                                </svg>
                            </div>
                        </div>

                        <button type="button"
                            wire:click="handleProductScan"
                            class="px-4 py-2.5 bg-blue-900 hover:bg-blue-800 text-white text-sm font-semibold rounded-xl transition shadow-sm cursor-pointer">
                            Buscar / Agregar
                        </button>
                    </div>

                    <!-- Autocomplete Search Dropdown -->
                    @if(strlen(trim($this->productQuery)) >= 2 && $this->foundMedicines->count() > 0)
                        <div x-show="dropdownOpen"
                            class="absolute z-20 mt-1 w-full bg-white rounded-xl shadow-lg border border-slate-200 max-h-64 overflow-y-auto divide-y divide-slate-100">
                            @foreach($this->foundMedicines as $med)
                                <button type="button"
                                    wire:click="openLotModal({{ $med->id }})"
                                    class="w-full text-left px-4 py-3 hover:bg-blue-50 transition flex items-center justify-between">
                                    <div>
                                        <div class="font-semibold text-sm text-slate-900">{{ $med->name }}</div>
                                        <div class="text-xs text-slate-500">
                                            {{ $med->presentation }} &bull; Precio: ${{ number_format((float) $med->selling_price, 0, ',', '.') }}
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-xs font-semibold px-2 py-0.5 rounded-full {{ $med->total_stock > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $med->total_stock }} disponibles
                                        </span>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Missing Medicine Alert & Quick Register -->
                @if($productSearchError)
                    <div class="mt-3 p-3 bg-amber-50 border border-amber-200 rounded-xl flex items-center justify-between gap-3">
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            <span class="text-xs font-medium text-amber-900">{{ $productSearchError }}</span>
                        </div>

                        <button type="button"
                            wire:click="openQuickMedicineModal"
                            class="text-xs font-bold text-blue-900 hover:text-blue-700 bg-white px-3 py-1.5 rounded-lg border border-amber-300 shadow-sm cursor-pointer whitespace-nowrap">
                            + Registrar Medicamento
                        </button>
                    </div>
                @endif
            </div>

            <!-- Card 3: Temporary Cart Items Table -->
            <div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-5">
                <div class="flex items-center justify-between mb-3">
                    <h2 class="text-base font-semibold text-blue-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <span>3. Renglones de la Venta ({{ count($cart) }})</span>
                    </h2>

                    <span class="text-xs font-medium text-slate-500">
                        {{ $this->cartUnitsCount }} unidades totales
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm border-collapse">
                        <thead>
                            <tr class="border-b border-slate-200 text-xs font-bold text-slate-500 uppercase">
                                <th class="py-2.5 px-3">Producto</th>
                                <th class="py-2.5 px-3">Lote</th>
                                <th class="py-2.5 px-3">Vence</th>
                                <th class="py-2.5 px-3 text-center">Cant.</th>
                                <th class="py-2.5 px-3 text-right">Precio Unit.</th>
                                <th class="py-2.5 px-3 text-right">Total</th>
                                <th class="py-2.5 px-3 text-center"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($cart as $key => $item)
                                <tr class="hover:bg-slate-50/70">
                                    <td class="py-3 px-3">
                                        <div class="font-semibold text-slate-900">{{ $item['medicine_name'] }}</div>
                                    </td>
                                    <td class="py-3 px-3">
                                        <span class="font-mono text-xs font-semibold px-2 py-0.5 rounded bg-slate-100 text-slate-800">
                                            {{ $item['batch_number'] }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 text-xs text-slate-600">
                                        {{ $item['expiration_date'] }}
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <input type="number" min="1" max="{{ $item['current_quantity'] }}"
                                            value="{{ $item['quantity'] }}"
                                            wire:change="updateCartItemQuantity('{{ $key }}', $event.target.value)"
                                            class="w-16 py-1 px-1.5 text-center text-xs border border-slate-200 rounded-lg focus:ring-1 focus:ring-blue-900">
                                    </td>
                                    <td class="py-3 px-3 text-right">
                                        <input type="number" min="0" step="1"
                                            value="{{ $item['unit_price'] }}"
                                            wire:change="updateCartItemPrice('{{ $key }}', $event.target.value)"
                                            class="w-24 py-1 px-1.5 text-right text-xs border border-slate-200 rounded-lg focus:ring-1 focus:ring-blue-900">
                                    </td>
                                    <td class="py-3 px-3 text-right font-bold text-slate-900">
                                        ${{ number_format((float) $item['subtotal'], 0, ',', '.') }}
                                    </td>
                                    <td class="py-3 px-3 text-center">
                                        <button type="button"
                                            wire:click="removeCartItem('{{ $key }}')"
                                            class="text-red-500 hover:text-red-700 p-1 rounded hover:bg-red-50 cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-8 text-center text-slate-400 text-sm">
                                        No hay productos agregados a la venta. Escanea un código de barras o busca un medicamento arriba.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @error('cart')
                    <div class="text-xs text-red-600 mt-2 font-medium">{{ $message }}</div>
                @enderror
                @error('items')
                    <div class="text-xs text-red-600 mt-2 font-medium">{{ $message }}</div>
                @enderror
            </div>

        </div>

        <!-- Right Column: Payment & Totals Summary -->
        <div class="space-y-6">

            <!-- Card: Payment Terms & Checkout -->
            <div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-5 space-y-4">
                <h2 class="text-base font-semibold text-blue-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                    </svg>
                    <span>4. Método de Pago</span>
                </h2>

                <!-- Payment Method Select -->
                <div class="space-y-2">
                    <label class="text-xs font-semibold text-slate-700">Forma de Pago</label>
                    <div class="grid grid-cols-3 gap-2">
                        <button type="button"
                            wire:click="$set('payment_method', 'cash')"
                            class="px-3 py-2 text-xs font-bold rounded-xl border text-center transition cursor-pointer {{ $payment_method === 'cash' ? 'bg-blue-900 text-white border-blue-900' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100' }}">
                            Efectivo
                        </button>
                        <button type="button"
                            wire:click="$set('payment_method', 'transfer')"
                            class="px-3 py-2 text-xs font-bold rounded-xl border text-center transition cursor-pointer {{ $payment_method === 'transfer' ? 'bg-blue-900 text-white border-blue-900' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100' }}">
                            Transferencia
                        </button>
                        <button type="button"
                            wire:click="$set('payment_method', 'credit')"
                            class="px-3 py-2 text-xs font-bold rounded-xl border text-center transition cursor-pointer {{ $payment_method === 'credit' ? 'bg-blue-900 text-white border-blue-900' : 'bg-slate-50 text-slate-700 border-slate-200 hover:bg-slate-100' }}">
                            Crédito
                        </button>
                    </div>
                </div>

                <!-- Conditional Credit Due Date -->
                @if($payment_method === 'credit')
                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-xl space-y-2">
                        <label for="payment_due_date" class="text-xs font-semibold text-amber-900">
                            Fecha Límite de Pago a Crédito *
                        </label>
                        <input type="date" id="payment_due_date"
                            wire:model="payment_due_date"
                            min="{{ now()->toDateString() }}"
                            class="w-full py-1.5 px-3 bg-white border border-amber-300 rounded-lg text-xs font-medium focus:ring-1 focus:ring-blue-900">
                        
                        @error('payment_due_date')
                            <div class="text-xs text-red-600 font-medium">{{ $message }}</div>
                        @enderror
                        @error('payment_method')
                            <div class="text-xs text-red-600 font-medium">{{ $message }}</div>
                        @enderror
                    </div>
                @endif

                <!-- Totals Breakdown -->
                <div class="pt-4 border-t border-slate-100 space-y-2 text-sm">
                    <div class="flex justify-between text-slate-600 text-xs">
                        <span>Subtotal de Productos</span>
                        <span>${{ number_format($this->invoiceTotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between text-slate-600 text-xs">
                        <span>Ajuste / Redondeo</span>
                        <span>$0</span>
                    </div>
                    <div class="flex justify-between items-baseline font-bold text-lg text-blue-950 pt-2 border-t border-slate-100">
                        <span>Total Factura</span>
                        <span class="text-2xl text-blue-900">
                            ${{ number_format($this->invoiceTotal, 0, ',', '.') }}
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-400 text-right">Valores en COP redondeados al peso entero.</p>
                </div>

                <!-- Finalize Action Button -->
                <button type="button"
                    wire:click="finalizeSale"
                    @disabled(count($cart) === 0 || ! $id_customer)
                    class="w-full py-3 px-4 bg-lime-500 hover:bg-lime-600 disabled:opacity-50 disabled:cursor-not-allowed text-blue-950 font-bold text-sm rounded-xl shadow-md transition duration-150 ease-in-out cursor-pointer flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>Finalizar Venta y Facturar</span>
                </button>
            </div>

        </div>

    </div>

    <!-- MODAL 1: FEFO Lot Selection Modal -->
    @if($showLotModal && $this->selectedMedicine)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-slate-200 overflow-hidden"
                @click.outside="$wire.closeLotModal()">
                
                <!-- Modal Header -->
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <span class="text-xs font-bold text-blue-700 uppercase tracking-wider">Asignación FEFO de Lotes</span>
                        <h3 class="text-base font-bold text-slate-900">{{ $this->selectedMedicine->name }}</h3>
                        <p class="text-xs text-slate-500">{{ $this->selectedMedicine->presentation }} &bull; Stock Total: {{ $this->selectedMedicine->total_stock }}</p>
                    </div>

                    <button type="button" wire:click="closeLotModal" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Body -->
                <div class="p-6 space-y-4">
                    <!-- Requested Quantity Control -->
                    <div class="flex items-center justify-between bg-blue-50/50 p-3 rounded-xl border border-blue-100">
                        <label for="requestedMedicineQuantity" class="text-xs font-bold text-blue-950">
                            Cantidad Solicitada por el Cliente:
                        </label>
                        <div class="flex items-center gap-2">
                            <input type="number" id="requestedMedicineQuantity" min="1" max="{{ $this->selectedMedicine->total_stock }}"
                                wire:model.live.debounce.300ms="requestedMedicineQuantity"
                                class="w-20 py-1 px-2 text-center text-sm font-bold border border-blue-300 rounded-lg focus:ring-1 focus:ring-blue-900 bg-white">
                            <span class="text-xs text-slate-500">unidades</span>
                        </div>
                    </div>

                    <!-- Available Lots Breakdown Table -->
                    <div class="overflow-x-auto border border-slate-200 rounded-xl">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                                <tr>
                                    <th class="py-2 px-3">Lote</th>
                                    <th class="py-2 px-3">Vence</th>
                                    <th class="py-2 px-3 text-center">Disponible</th>
                                    <th class="py-2 px-3 text-center">Asignado</th>
                                    <th class="py-2 px-3 text-center">Bloquear</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($fefoAllocations as $alloc)
                                    <tr class="{{ $alloc['is_fefo_priority'] ? 'bg-lime-50/40' : '' }}">
                                        <td class="py-2.5 px-3 font-semibold">
                                            <div class="flex items-center gap-1.5">
                                                <span>{{ $alloc['batch_number'] }}</span>
                                                @if($alloc['is_fefo_priority'])
                                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-lime-200 text-lime-900">FEFO</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="py-2.5 px-3 text-slate-600">
                                            {{ $alloc['expiration_date'] }}
                                        </td>
                                        <td class="py-2.5 px-3 text-center font-bold text-slate-700">
                                            {{ $alloc['current_quantity'] }}
                                        </td>
                                        <td class="py-2.5 px-3 text-center">
                                            <input type="number" min="0" max="{{ $alloc['current_quantity'] }}"
                                                value="{{ $alloc['allocated_quantity'] }}"
                                                wire:change="updateLockedLotQuantity({{ $alloc['lot_id'] }}, $event.target.value)"
                                                class="w-16 py-0.5 px-1.5 text-center text-xs font-bold border border-slate-300 rounded focus:ring-1 focus:ring-blue-900 bg-white">
                                        </td>
                                        <td class="py-2.5 px-3 text-center">
                                            <button type="button"
                                                wire:click="toggleLotLock({{ $alloc['lot_id'] }})"
                                                class="p-1 rounded cursor-pointer transition {{ $alloc['is_locked'] ? 'text-amber-600 bg-amber-100' : 'text-slate-400 hover:text-slate-600' }}"
                                                title="{{ $alloc['is_locked'] ? 'Fila bloqueada (no cambia automáticamente)' : 'Bloquear asignación' }}">
                                                @if($alloc['is_locked'])
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                                    </svg>
                                                @else
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                                                    </svg>
                                                @endif
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="py-4 text-center text-slate-400">
                                            No hay lotes con existencias disponibles para este medicamento.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="px-6 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" wire:click="closeLotModal"
                        class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 cursor-pointer">
                        Cancelar
                    </button>

                    <button type="button" wire:click="addAllocationsToCart"
                        class="px-5 py-2 bg-blue-900 hover:bg-blue-800 text-white font-bold text-xs rounded-xl shadow-sm cursor-pointer">
                        Agregar a la Venta
                    </button>
                </div>

            </div>
        </div>
    @endif

    <!-- MODAL 2: Quick Medicine Creation Modal -->
    @if($showQuickMedicineModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-xl w-full shadow-2xl border border-slate-200 overflow-hidden"
                @click.outside="$wire.closeQuickMedicineModal()">
                
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Registrar Nuevo Medicamento Rápido</h3>
                        <p class="text-xs text-slate-500">Crea el producto maestro sin salir de la pantalla de venta.</p>
                    </div>

                    <button type="button" wire:click="closeQuickMedicineModal" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form wire:submit="saveQuickMedicine" class="p-6 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-semibold text-slate-700">Nombre Comercial *</label>
                            <input type="text" wire:model="quickName" required
                                class="w-full py-1.5 px-3 border border-slate-200 rounded-lg text-xs bg-slate-50">
                            @error('quickName') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-slate-700">Código de Barras</label>
                            <input type="text" wire:model="quickBarcode"
                                class="w-full py-1.5 px-3 border border-slate-200 rounded-lg text-xs bg-slate-50">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="text-xs font-semibold text-slate-700">Nombre Genérico</label>
                            <input type="text" wire:model="quickGenericName"
                                class="w-full py-1.5 px-3 border border-slate-200 rounded-lg text-xs bg-slate-50">
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-slate-700">Precio de Venta ($) *</label>
                            <input type="number" min="0" step="1" wire:model="quickSellingPrice" required
                                class="w-full py-1.5 px-3 border border-slate-200 rounded-lg text-xs font-bold bg-slate-50">
                            @error('quickSellingPrice') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        <div>
                            <label class="text-xs font-semibold text-slate-700">Categoría</label>
                            <select wire:model="quickCategoryId" class="w-full py-1.5 px-2 text-xs border border-slate-200 rounded-lg bg-slate-50">
                                @foreach(\App\Models\Category::all() as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-slate-700">Laboratorio</label>
                            <select wire:model="quickLaboratoryId" class="w-full py-1.5 px-2 text-xs border border-slate-200 rounded-lg bg-slate-50">
                                @foreach(\App\Models\Laboratory::all() as $lab)
                                    <option value="{{ $lab->id }}">{{ $lab->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="text-xs font-semibold text-slate-700">Registro Sanitario</label>
                            <select wire:model="quickSanitaryRegistryId" class="w-full py-1.5 px-2 text-xs border border-slate-200 rounded-lg bg-slate-50">
                                @foreach(\App\Models\SanitaryRegistry::all() as $reg)
                                    <option value="{{ $reg->id }}">{{ $reg->registration_number }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-3 border-t border-slate-100">
                        <button type="button" wire:click="closeQuickMedicineModal"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 cursor-pointer">
                            Cancelar
                        </button>
                        <button type="submit"
                            class="px-5 py-2 bg-blue-900 hover:bg-blue-800 text-white font-bold text-xs rounded-xl shadow-sm cursor-pointer">
                            Guardar y Seleccionar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- MODAL 3: Post-Sale Confirmation & PDF Actions -->
    @if($showSuccessModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-200 p-6 text-center space-y-4">
                
                <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>

                <div>
                    <h3 class="text-lg font-bold text-slate-900">¡Venta y Factura Generadas!</h3>
                    <p class="text-sm font-mono font-bold text-blue-900 mt-1">
                        {{ $createdInvoiceNumber }}
                    </p>
                    <p class="text-xs text-slate-500 mt-1">
                        El inventario ha sido descargado de los lotes correspondientes y el movimiento quedó auditado.
                    </p>
                </div>

                <div class="space-y-2 pt-2">
                    <a href="{{ route('bills.pdf', $createdBillId) }}" target="_blank"
                        class="w-full py-2.5 px-4 bg-blue-900 hover:bg-blue-800 text-white text-xs font-bold rounded-xl shadow-sm transition flex items-center justify-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span>Descargar / Imprimir Factura PDF</span>
                    </a>

                    <button type="button" wire:click="$set('showSuccessModal', false)"
                        class="w-full py-2.5 px-4 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold rounded-xl transition cursor-pointer">
                        Continuar Nueva Venta
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>


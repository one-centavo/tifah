<?php

declare(strict_types=1);

use App\Models\Lot;
use App\Models\Medicine;
use App\Models\MedicineBarcode;
use App\Models\Supplier;
use App\Services\LotService;
use App\Services\SupplierService;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    // Tabs
    public string $activeTab = 'consolidated'; // 'consolidated' or 'reception'

    // Consolidated/Inventario Tab Properties
    public string $search = '';

    // Reception Tab Properties
    public string $barcode = '';

    public ?int $selectedMedicineId = null;

    public string $selectedMedicineName = '';

    public float $selectedMedicineSellingPrice = 0.0;

    // Batch Fields
    public string $batch_number = '';

    public string $expiration_date = '';

    public string $quantity = '';

    public string $unit_purchase_price = '';

    public string $reception_date = '';

    public string $supplier_id = '';

    public string $status = 'active'; // default

    // Temporary list state
    public array $temporaryLots = [];

    // Supplier modal fields
    public bool $showSupplierModal = false;

    public string $supplier_nit = '';

    public string $supplier_dv = '';

    public string $supplier_name = '';

    public string $supplier_contact_person = '';

    public string $supplier_phone_number = '';

    public string $supplier_email = '';

    public string $supplier_address = '';

    public function mount(): void
    {
        $this->reception_date = now()->format('Y-m-d');
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Triggered when barcode is changed or scanned.
     */
    public function updatedBarcode(): void
    {
        if (empty($this->barcode)) {
            $this->resetMedicineSelection();

            return;
        }

        $barcodeRecord = MedicineBarcode::where('barcode', $this->barcode)
            ->whereHas('medicine')
            ->first();

        if ($barcodeRecord) {
            $medicine = $barcodeRecord->medicine;
            $this->selectedMedicineId = $medicine->id;
            $this->selectedMedicineName = $medicine->name;
            $this->selectedMedicineSellingPrice = (float) $medicine->selling_price;
            $this->resetErrorBag('barcode');
        } else {
            $this->resetMedicineSelection();
            $this->addError('barcode', 'El código de barras no coincide con ningún medicamento registrado.');
        }
    }

    private function resetMedicineSelection(): void
    {
        $this->selectedMedicineId = null;
        $this->selectedMedicineName = '';
        $this->selectedMedicineSellingPrice = 0.0;
    }

    /**
     * Add the current form values to the temporary list.
     */
    public function addToTemporaryList(): void
    {
        if (! $this->selectedMedicineId) {
            $this->addError('barcode', 'Debe seleccionar o escanear un producto válido.');

            return;
        }

        $this->validate([
            'batch_number' => ['required', 'alpha_num'],
            'expiration_date' => ['required', 'date'],
            'quantity' => ['required', 'integer', 'min:1'],
            'unit_purchase_price' => ['required', 'numeric', 'min:0.01'],
            'reception_date' => ['required', 'date'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'status' => ['required', 'in:active,blocked,damaged'],
        ], [
            'batch_number.required' => 'El número de lote es obligatorio.',
            'batch_number.alpha_num' => 'El número de lote debe contener únicamente letras y números.',
            'expiration_date.required' => 'La fecha de vencimiento es obligatoria.',
            'quantity.required' => 'La cantidad recibida es obligatoria.',
            'quantity.integer' => 'La cantidad recibida debe ser un número entero.',
            'quantity.min' => 'La cantidad recibida debe ser mayor a cero.',
            'unit_purchase_price.required' => 'El costo de compra es obligatorio.',
            'unit_purchase_price.numeric' => 'El costo de compra debe ser un número.',
            'unit_purchase_price.min' => 'El costo de compra debe ser mayor a cero.',
            'reception_date.required' => 'La fecha de recepción es obligatoria.',
            'supplier_id.required' => 'El proveedor es obligatorio.',
        ]);

        // Expiry Date Check
        $expirationDate = Carbon::parse($this->expiration_date);
        if ($expirationDate->isPast() && ! $expirationDate->isToday()) {
            $this->addError('expiration_date', 'No se pueden ingresar productos vencidos.');

            return;
        }

        $supplier = Supplier::find($this->supplier_id);
        $supplierName = $supplier ? $supplier->name : 'N/A';

        // Check duplicate product + batch in temporary list
        $foundIndex = null;
        foreach ($this->temporaryLots as $idx => $tempLot) {
            if ($tempLot['medicine_id'] === $this->selectedMedicineId && $tempLot['batch_number'] === $this->batch_number) {
                $foundIndex = $idx;
                break;
            }
        }

        if ($foundIndex !== null) {
            // Merge
            $this->temporaryLots[$foundIndex]['quantity'] += (int) $this->quantity;
            $this->temporaryLots[$foundIndex]['total_price'] = $this->temporaryLots[$foundIndex]['quantity'] * $this->temporaryLots[$foundIndex]['unit_purchase_price'];
        } else {
            // Append
            $this->temporaryLots[] = [
                'medicine_id' => $this->selectedMedicineId,
                'medicine_name' => $this->selectedMedicineName,
                'batch_number' => $this->batch_number,
                'expiration_date' => $this->expiration_date,
                'quantity' => (int) $this->quantity,
                'unit_purchase_price' => (float) $this->unit_purchase_price,
                'total_price' => (int) $this->quantity * (float) $this->unit_purchase_price,
                'supplier_id' => (int) $this->supplier_id,
                'supplier_name' => $supplierName,
                'status' => $this->status,
            ];
        }

        // Reset batch input fields, keep supplier and reception_date as defaults
        $this->barcode = '';
        $this->resetMedicineSelection();
        $this->batch_number = '';
        $this->expiration_date = '';
        $this->quantity = '';
        $this->unit_purchase_price = '';
        $this->status = 'active';
        $this->resetErrorBag();
    }

    /**
     * Edit a row from the temporary list.
     */
    public function editTemporaryLot(int $index): void
    {
        $lot = $this->temporaryLots[$index];

        $medicine = Medicine::find($lot['medicine_id']);
        if ($medicine) {
            $this->selectedMedicineId = $medicine->id;
            $this->selectedMedicineName = $medicine->name;
            $this->selectedMedicineSellingPrice = (float) $medicine->selling_price;
            $mainBarcode = $medicine->barcodes()->where('is_main', true)->first();
            $this->barcode = $mainBarcode ? $mainBarcode->barcode : '';
        }

        $this->batch_number = $lot['batch_number'];
        $this->expiration_date = $lot['expiration_date'];
        $this->quantity = (string) $lot['quantity'];
        $this->unit_purchase_price = (string) $lot['unit_purchase_price'];
        $this->supplier_id = (string) $lot['supplier_id'];
        $this->status = $lot['status'];

        unset($this->temporaryLots[$index]);
        $this->temporaryLots = array_values($this->temporaryLots);
    }

    /**
     * Remove a row from the temporary list.
     */
    public function removeTemporaryLot(int $index): void
    {
        unset($this->temporaryLots[$index]);
        $this->temporaryLots = array_values($this->temporaryLots);
    }

    /**
     * Save the temporary list to the database permanently.
     */
    public function confirmReception(LotService $lotService): void
    {
        if (empty($this->temporaryLots)) {
            $this->addError('reception_error', 'Debe agregar al menos un lote a la lista.');

            return;
        }

        $lotService->receiveMerchandise(
            $this->temporaryLots,
            (int) $this->supplier_id,
            $this->reception_date
        );

        $this->temporaryLots = [];
        $this->reset(['barcode', 'selectedMedicineId', 'selectedMedicineName', 'selectedMedicineSellingPrice', 'batch_number', 'expiration_date', 'quantity', 'unit_purchase_price', 'status']);
        $this->reception_date = now()->format('Y-m-d');

        session()->flash('success', 'Ingreso de mercancía registrado con éxito.');
        $this->activeTab = 'consolidated';
        $this->resetPage();
    }

    /**
     * Cancel the reception and clear temporary state.
     */
    public function cancelReception(): void
    {
        $this->temporaryLots = [];
        $this->reset(['barcode', 'selectedMedicineId', 'selectedMedicineName', 'selectedMedicineSellingPrice', 'batch_number', 'expiration_date', 'quantity', 'unit_purchase_price', 'status']);
        $this->reception_date = now()->format('Y-m-d');
        $this->activeTab = 'consolidated';
    }

    /**
     * Open quick supplier registration modal.
     */
    public function openQuickSupplierModal(): void
    {
        $this->resetSupplierForm();
        $this->showSupplierModal = true;
    }

    /**
     * Close quick supplier modal.
     */
    public function closeQuickSupplierModal(): void
    {
        $this->showSupplierModal = false;
        $this->resetSupplierForm();
    }

    private function resetSupplierForm(): void
    {
        $this->reset(['supplier_nit', 'supplier_dv', 'supplier_name', 'supplier_contact_person', 'supplier_phone_number', 'supplier_email', 'supplier_address']);
        $this->resetErrorBag();
    }

    /**
     * Save quick supplier registration.
     */
    public function saveQuickSupplier(SupplierService $supplierService): void
    {
        $this->validate([
            'supplier_nit' => [
                'required',
                \Illuminate\Validation\Rule::unique('suppliers', 'nit')->whereNull('deleted_at'),
            ],
            'supplier_dv' => ['required', 'integer', 'min:0', 'max:9'],
            'supplier_name' => ['required', 'string', 'max:150'],
            'supplier_contact_person' => ['required', 'string', 'max:100'],
            'supplier_phone_number' => ['required', 'string', 'max:20'],
            'supplier_email' => ['required', 'email', 'max:255'],
            'supplier_address' => ['required', 'string', 'max:255'],
        ], [
            'supplier_nit.required' => 'El NIT es obligatorio.',
            'supplier_nit.unique' => 'Este NIT ya está registrado.',
            'supplier_dv.required' => 'El dígito de verificación (DV) es obligatorio.',
            'supplier_name.required' => 'El nombre es obligatorio.',
            'supplier_contact_person.required' => 'La persona de contacto es obligatoria.',
            'supplier_phone_number.required' => 'El teléfono es obligatorio.',
            'supplier_email.required' => 'El correo electrónico es obligatorio.',
            'supplier_email.email' => 'El correo electrónico debe ser válido.',
            'supplier_address.required' => 'La dirección es obligatoria.',
        ]);

        $newSupplier = $supplierService->create([
            'nit' => $this->supplier_nit,
            'dv' => (int) $this->supplier_dv,
            'name' => $this->supplier_name,
            'contact_person' => $this->supplier_contact_person,
            'phone_number' => $this->supplier_phone_number,
            'email' => $this->supplier_email,
            'address' => $this->supplier_address,
        ]);

        $this->supplier_id = (string) $newSupplier->id;
        $this->showSupplierModal = false;
        $this->resetSupplierForm();
    }

    public function with(): array
    {
        $medicineQuery = Medicine::query()
            ->with(['concentrationUnit'])
            ->withCount(['lots as active_lots_count' => function ($q) {
                $q->where('status', 'active');
            }])
            ->withSum(['lots as total_stock' => function ($q) {
                $q->where('status', 'active');
            }], 'current_quantity');

        if (! empty(trim($this->search))) {
            $term = '%'.trim($this->search).'%';
            $medicineQuery->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('generic_name', 'like', $term)
                    ->orWhereHas('barcodes', function ($bq) use ($term) {
                        $bq->where('barcode', 'like', $term);
                    });
            });
        }

        $medicineQuery->orderBy('name', 'asc');

        return [
            'medicines' => $medicineQuery->paginate(15),
            'suppliers' => Supplier::orderBy('name')->get(),
        ];
    }
}; ?>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-blue-900 dark:text-white tracking-tight">Gestión de Inventario</h1>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-2">
                Consulta la consolidación de existencias por medicamento y registra el ingreso de nueva mercancía.
            </p>
        </div>
    </div>

    <!-- Alert Success -->
    @if (session()->has('success'))
        <div class="mb-6 p-4 bg-lime-50 border border-lime-200 text-lime-800 rounded-xl flex items-center shadow-sm animate-pulse" role="alert" id="success-alert">
            <svg class="w-5 h-5 mr-2 text-lime-600 shrink-0" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    <!-- Tabs Nav -->
    <div class="border-b border-slate-200 mb-6">
        <ul class="flex flex-wrap -mb-px text-sm font-medium text-center">
            <li class="mr-2">
                <button type="button" wire:click="switchTab('consolidated')"
                    class="inline-flex items-center gap-2 p-4 border-b-2 rounded-t-lg transition-all duration-150 {{ $activeTab === 'consolidated' ? 'text-blue-900 border-blue-900 font-bold' : 'text-slate-500 border-transparent hover:text-slate-800 hover:border-slate-300' }}">
                    <x-tabler-packages class="w-5 h-5" />
                    <span>Inventario</span>
                </button>
            </li>
            <li class="mr-2">
                <button type="button" wire:click="switchTab('reception')"
                    class="inline-flex items-center gap-2 p-4 border-b-2 rounded-t-lg transition-all duration-150 {{ $activeTab === 'reception' ? 'text-blue-900 border-blue-900 font-bold' : 'text-slate-500 border-transparent hover:text-slate-800 hover:border-slate-300' }}">
                    <x-tabler-package-import class="w-5 h-5" />
                    <span>Recepción de Mercancía</span>
                </button>
            </li>
        </ul>
    </div>

    <!-- Tab 1: Inventario Consolidado -->
    @if ($activeTab === 'consolidated')
        <!-- Filters -->
        <div class="bg-white border border-slate-100 shadow-sm rounded-2xl p-6 mb-6">
            <h2 class="text-lg font-bold text-blue-900 mb-4">Filtrar Medicamentos</h2>
            <div class="grid grid-cols-1 gap-4">
                <!-- Search -->
                <div>
                    <label for="search" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Búsqueda</label>
                    <div class="relative">
                        <input type="text" id="search" wire:model.live.debounce.300ms="search" placeholder="Buscar medicamento por nombre o código de barras..."
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                        @if($search)
                            <button type="button" wire:click="$set('search', '')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white border border-slate-100 shadow-sm rounded-2xl overflow-hidden">
            @if($medicines->isEmpty())
                <div class="p-12 text-center">
                    <div class="inline-flex p-4 bg-slate-50 text-slate-400 rounded-full mb-4">
                        <x-tabler-packages class="w-12 h-12" />
                    </div>
                    <h3 class="text-lg font-bold text-blue-900 mb-1">Sin medicamentos registrados</h3>
                    <p class="text-sm text-slate-500">No se encontraron medicamentos que coincidan con la búsqueda.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase tracking-wider">Nombre Comercial</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase tracking-wider">Nombre Genérico</th>
                                <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase tracking-wider">Concentración</th>
                                <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-blue-900 uppercase tracking-wider">Stock Total (Unidades)</th>
                                <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-blue-900 uppercase tracking-wider">Cantidad de Lotes Activos</th>
                                <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-blue-900 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach($medicines as $medicine)
                                <tr class="hover:bg-slate-50/50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-blue-900">{{ $medicine->name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-slate-600">{{ $medicine->generic_name ?: 'N/A' }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                        {{ $medicine->concentration_formatted }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-semibold text-slate-800">
                                        {{ $medicine->total_stock ?? 0 }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-semibold text-slate-800">
                                        {{ $medicine->active_lots_count }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('inventory.medicine-lots', $medicine->id) }}" wire:navigate
                                            class="inline-flex items-center text-blue-900 hover:text-lime-500 font-bold transition-colors gap-1">
                                            <x-tabler-eye class="w-4 h-4" />
                                            <span>Ver Detalle</span>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100">
                    {{ $medicines->links() }}
                </div>
            @endif
        </div>
    @endif

    <!-- Tab 2: Recepción de Mercancía -->
    @if ($activeTab === 'reception')
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Form Card -->
            <div class="lg:col-span-2 bg-white border border-slate-100 shadow-sm rounded-2xl p-6">
                <h2 class="text-xl font-bold text-blue-900 mb-6">Detalles de Entrada</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Barcode Scan -->
                    <div class="md:col-span-2">
                        <label for="barcode" class="block text-sm font-semibold text-slate-700 mb-2">Escanear o digitar Código de Barras</label>
                        <div class="relative">
                            <input type="text" id="barcode" wire:model.live.debounce.500ms="barcode" placeholder="Escriba o escanee..."
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-mono">
                        </div>
                        <x-input-error :messages="$errors->get('barcode')" class="mt-2" />
                    </div>

                    <!-- Medicine Auto-Discovered -->
                    @if ($selectedMedicineId)
                        <div class="md:col-span-2 bg-blue-50/50 border border-blue-100 rounded-xl p-4 flex justify-between items-center animate-fade-in">
                            <div>
                                <span class="text-xs uppercase font-extrabold tracking-wider text-blue-800">Producto detectado</span>
                                <h3 class="text-lg font-bold text-blue-950 mt-1">{{ $selectedMedicineName }}</h3>
                                <p class="text-sm text-blue-900/70 font-mono mt-0.5">Precio de Venta: ${{ number_format($selectedMedicineSellingPrice, 2) }}</p>
                            </div>
                            <button type="button" wire:click="$set('barcode', '')" class="text-slate-400 hover:text-slate-600">
                                <x-tabler-x class="w-5 h-5" />
                            </button>
                        </div>
                    @else
                        <!-- Button to add new medicine when lookup has failed -->
                        @if($barcode && $errors->has('barcode'))
                            <div class="md:col-span-2 bg-rose-50 border border-rose-100 rounded-xl p-4 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                                <div class="text-rose-900 text-sm">
                                    El medicamento con código de barras <strong class="font-mono">"{{ $barcode }}"</strong> no existe. ¿Desea registrarlo?
                                </div>
                                <a href="{{ route('medicines.create') }}" target="_blank"
                                    class="inline-flex items-center bg-blue-900 hover:bg-lime-500 hover:text-blue-950 text-white text-xs font-semibold px-4 py-2 rounded-lg transition-colors gap-1">
                                    <x-tabler-plus class="w-4 h-4" />
                                    <span>Registrar Medicamento</span>
                                </a>
                            </div>
                        @endif
                    @endif

                    <!-- Batch Number -->
                    <div>
                        <label for="batch_number" class="block text-sm font-semibold text-slate-700 mb-2">Número de Lote</label>
                        <input type="text" id="batch_number" wire:model="batch_number" placeholder="Alfanumérico..."
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-mono">
                        <x-input-error :messages="$errors->get('batch_number')" class="mt-2" />
                    </div>

                    <!-- Expiration Date -->
                    <div>
                        <label for="expiration_date" class="block text-sm font-semibold text-slate-700 mb-2">Fecha de Vencimiento</label>
                        <input type="date" id="expiration_date" wire:model="expiration_date"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                        <x-input-error :messages="$errors->get('expiration_date')" class="mt-2" />
                    </div>

                    <!-- Quantity Received -->
                    <div>
                        <label for="quantity" class="block text-sm font-semibold text-slate-700 mb-2">Cantidad Recibida</label>
                        <input type="number" id="quantity" wire:model.live="quantity" placeholder="Unidades físicas..." min="1" step="1"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-mono">
                        <x-input-error :messages="$errors->get('quantity')" class="mt-2" />
                    </div>

                    <!-- Unit Purchase Cost -->
                    <div>
                        <label for="unit_purchase_price" class="block text-sm font-semibold text-slate-700 mb-2">Costo Compra Unitario</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400">$</span>
                            <input type="number" id="unit_purchase_price" wire:model.live="unit_purchase_price" placeholder="0.00" min="0.01" step="0.01"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl pl-8 pr-4 py-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-mono">
                        </div>
                        <x-input-error :messages="$errors->get('unit_purchase_price')" class="mt-2" />
                    </div>

                    <!-- Status Selection -->
                    <div>
                        <label for="status" class="block text-sm font-semibold text-slate-700 mb-2">Estado del Lote</label>
                        <select id="status" wire:model="status"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all cursor-pointer">
                            <option value="active">Activo (Disponible de inmediato)</option>
                            <option value="blocked">Bloqueado</option>
                            <option value="damaged">Dañado / Averiado</option>
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>

                    <!-- Reception Date -->
                    <div>
                        <label for="reception_date" class="block text-sm font-semibold text-slate-700 mb-2">Fecha de Recepción</label>
                        <input type="date" id="reception_date" wire:model="reception_date"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                        <x-input-error :messages="$errors->get('reception_date')" class="mt-2" />
                    </div>

                    <!-- Supplier Dropdown with Quick Add -->
                    <div class="md:col-span-2">
                        <div class="flex justify-between items-center mb-2">
                            <label for="supplier_id" class="block text-sm font-semibold text-slate-700">Proveedor</label>
                            <button type="button" wire:click="openQuickSupplierModal"
                                class="inline-flex items-center text-blue-900 hover:text-lime-500 font-bold text-xs gap-1 cursor-pointer">
                                <x-tabler-plus class="w-3.5 h-3.5" />
                                <span>Rápido Proveedor</span>
                            </button>
                        </div>
                        <select id="supplier_id" wire:model="supplier_id"
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all cursor-pointer">
                            <option value="">-- Seleccione un proveedor --</option>
                            @foreach ($suppliers as $sup)
                                <option value="{{ $sup->id }}">{{ $sup->name }} (NIT: {{ $sup->nit }}-{{ $sup->dv }})</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('supplier_id')" class="mt-2" />
                    </div>
                </div>

                <!-- Margin Warning and Total Calculation -->
                <div class="mt-6 pt-6 border-t border-slate-100 space-y-4">
                    <!-- Calculations -->
                    @if ((int)$quantity > 0 && (float)$unit_purchase_price > 0)
                        <div class="flex justify-between items-center bg-slate-50 rounded-xl p-4 font-mono text-sm text-slate-700">
                            <span>Subtotal del lote:</span>
                            <span class="font-bold text-lg text-slate-900">${{ number_format((int)$quantity * (float)$unit_purchase_price, 2) }}</span>
                        </div>
                    @endif

                    <!-- Margin Warning -->
                    @if ($selectedMedicineSellingPrice > 0 && (float)$unit_purchase_price > $selectedMedicineSellingPrice)
                        <div class="p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl flex items-start gap-2.5 animate-bounce">
                            <x-tabler-alert-triangle class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" />
                            <div class="text-sm">
                                <span class="font-bold">Advertencia de rentabilidad:</span> El costo de compra por unidad (<strong>${{ number_format((float)$unit_purchase_price, 2) }}</strong>) es mayor al precio de venta asignado para este medicamento (<strong>${{ number_format($selectedMedicineSellingPrice, 2) }}</strong>).
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Form Add Action -->
                <div class="mt-6 flex justify-end">
                    <button type="button" wire:click="addToTemporaryList"
                        class="inline-flex items-center bg-blue-900 hover:bg-lime-500 hover:text-blue-950 text-white font-semibold px-6 py-3 rounded-xl shadow-md transition-all gap-2 cursor-pointer transform hover:-translate-y-0.5">
                        <x-tabler-plus class="w-5 h-5" />
                        <span>Añadir a la lista</span>
                    </button>
                </div>
            </div>

            <!-- List Summary Card -->
            <div class="bg-white border border-slate-100 shadow-sm rounded-2xl p-6 flex flex-col justify-between">
                <div>
                    <h2 class="text-xl font-bold text-blue-900 mb-6">Resumen del Ingreso</h2>

                    @if (empty($temporaryLots))
                        <div class="text-center py-12 text-slate-400">
                            <x-tabler-shopping-cart class="w-12 h-12 mx-auto mb-4 text-slate-300" />
                            <p class="text-sm">No has agregado ningún medicamento a la lista.</p>
                            <p class="text-xs text-slate-400 mt-1">Escanea un producto e ingresa los datos para empezar.</p>
                        </div>
                    @else
                        <div class="space-y-4 max-h-[400px] overflow-y-auto pr-2 divide-y divide-slate-100">
                            @foreach ($temporaryLots as $index => $item)
                                <div class="pt-4 first:pt-0 flex flex-col justify-between gap-2">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <h4 class="text-sm font-bold text-blue-950">{{ $item['medicine_name'] }}</h4>
                                            <div class="text-xs text-slate-500 mt-0.5 space-x-2">
                                                <span>Lote: <strong class="font-mono text-slate-700">{{ $item['batch_number'] }}</strong></span>
                                                <span>Cant: <strong class="text-slate-700">{{ $item['quantity'] }}</strong></span>
                                            </div>
                                            <div class="text-xs text-slate-500 mt-0.5">
                                                <span>Vence: <strong class="text-slate-700">{{ \Carbon\Carbon::parse($item['expiration_date'])->format('d/m/Y') }}</strong></span>
                                            </div>
                                        </div>
                                        <div class="text-right font-mono text-sm font-semibold text-slate-900">
                                            ${{ number_format($item['total_price'], 2) }}
                                        </div>
                                    </div>
                                    <div class="flex justify-between items-center text-xs">
                                        <div>
                                            @if($item['status'] === 'blocked')
                                                <span class="bg-amber-50 text-amber-700 border border-amber-100 px-2 py-0.5 rounded text-[10px] uppercase font-semibold">Bloqueado</span>
                                            @elseif($item['status'] === 'damaged')
                                                <span class="bg-red-50 text-red-700 border border-red-100 px-2 py-0.5 rounded text-[10px] uppercase font-semibold">Dañado</span>
                                            @else
                                                <span class="bg-lime-50 text-lime-700 border border-lime-100 px-2 py-0.5 rounded text-[10px] uppercase font-semibold">Activo</span>
                                            @endif
                                        </div>
                                        <div class="flex gap-2">
                                            <button type="button" wire:click="editTemporaryLot({{ $index }})" class="text-blue-600 hover:text-blue-900 font-semibold cursor-pointer">Editar</button>
                                            <button type="button" wire:click="removeTemporaryLot({{ $index }})" class="text-red-600 hover:text-red-900 font-semibold cursor-pointer">Quitar</button>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                @if (!empty($temporaryLots))
                    <div class="mt-6 pt-6 border-t border-slate-100">
                        @php
                            $grandTotal = array_sum(array_column($temporaryLots, 'total_price'));
                        @endphp
                        <div class="flex justify-between items-center text-blue-900 mb-6">
                            <span class="text-sm font-bold uppercase tracking-wider">Costo Total Recepción:</span>
                            <span class="text-2xl font-black font-mono">${{ number_format($grandTotal, 2) }}</span>
                        </div>

                        <x-input-error :messages="$errors->get('reception_error')" class="mb-4" />

                        <div class="grid grid-cols-2 gap-3">
                            <button type="button" wire:click="cancelReception"
                                class="inline-flex justify-center items-center bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-3 rounded-xl transition-all cursor-pointer">
                                Cancelar
                            </button>
                            <button type="button" wire:click="confirmReception"
                                class="inline-flex justify-center items-center bg-lime-500 hover:bg-lime-600 text-blue-950 font-bold py-3 rounded-xl transition-all shadow-sm cursor-pointer">
                                Confirmar Ingreso
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif

    <!-- Quick Supplier Modal -->
    @if ($showSupplierModal)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl border border-slate-100 shadow-xl max-w-lg w-full overflow-hidden animate-zoom-in">
                <!-- Modal Header -->
                <div class="bg-blue-900 text-white px-6 py-4 flex justify-between items-center">
                    <h3 class="text-lg font-bold">Registro Rápido de Proveedor</h3>
                    <button type="button" wire:click="closeQuickSupplierModal" class="text-white/80 hover:text-white">
                        <x-tabler-x class="w-5 h-5" />
                    </button>
                </div>

                <!-- Modal Body -->
                <form wire:submit.prevent="saveQuickSupplier" class="p-6 space-y-4">
                    <div class="grid grid-cols-3 gap-4">
                        <!-- NIT -->
                        <div class="col-span-2">
                            <label for="supplier_nit" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">NIT</label>
                            <input type="text" id="supplier_nit" wire:model="supplier_nit" placeholder="Ej: 900123456"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-mono">
                            <x-input-error :messages="$errors->get('supplier_nit')" class="mt-1" />
                        </div>
                        <!-- DV -->
                        <div>
                            <label for="supplier_dv" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">DV</label>
                            <input type="number" id="supplier_dv" wire:model="supplier_dv" placeholder="0" min="0" max="9"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-mono">
                            <x-input-error :messages="$errors->get('supplier_dv')" class="mt-1" />
                        </div>
                    </div>

                    <!-- Name -->
                    <div>
                        <label for="supplier_name" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Nombre / Razón Social</label>
                        <input type="text" id="supplier_name" wire:model="supplier_name" placeholder="Nombre de la empresa..."
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                        <x-input-error :messages="$errors->get('supplier_name')" class="mt-1" />
                    </div>

                    <!-- Contact Person -->
                    <div>
                        <label for="supplier_contact_person" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Persona de Contacto</label>
                        <input type="text" id="supplier_contact_person" wire:model="supplier_contact_person" placeholder="Nombre completo..."
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                        <x-input-error :messages="$errors->get('supplier_contact_person')" class="mt-1" />
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Phone -->
                        <div>
                            <label for="supplier_phone_number" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Teléfono</label>
                            <input type="text" id="supplier_phone_number" wire:model="supplier_phone_number" placeholder="Ej: +57 3001234567"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-mono">
                            <x-input-error :messages="$errors->get('supplier_phone_number')" class="mt-1" />
                        </div>
                        <!-- Email -->
                        <div>
                            <label for="supplier_email" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Correo Electrónico</label>
                            <input type="email" id="supplier_email" wire:model="supplier_email" placeholder="proveedor@empresa.com"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all font-mono">
                            <x-input-error :messages="$errors->get('supplier_email')" class="mt-1" />
                        </div>
                    </div>

                    <!-- Address -->
                    <div>
                        <label for="supplier_address" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-1">Dirección de Despacho</label>
                        <input type="text" id="supplier_address" wire:model="supplier_address" placeholder="Dirección física..."
                            class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
                        <x-input-error :messages="$errors->get('supplier_address')" class="mt-1" />
                    </div>

                    <!-- Modal Actions -->
                    <div class="mt-6 flex justify-end gap-3 pt-4 border-t border-slate-100">
                        <x-secondary-button type="button" wire:click="closeQuickSupplierModal" class="cursor-pointer">
                            Cancelar
                        </x-secondary-button>
                        <button type="submit"
                            class="inline-flex items-center bg-blue-900 hover:bg-lime-500 hover:text-blue-950 text-white font-semibold px-4 py-2 rounded-xl transition-all cursor-pointer">
                            Guardar Proveedor
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>

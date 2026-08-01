<?php

declare(strict_types=1);

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Category;
use App\Models\ConcentrationUnit;
use App\Models\Container;
use App\Models\ContentUnit;
use App\Models\Laboratory;
use App\Models\Medicine;
use App\Models\MedicineBarcode;
use App\Models\SanitaryRegistry;
use App\Services\MedicineService;
use App\Services\LaboratoryService;
use App\Services\SanitaryRegistryService;
use Illuminate\Validation\ValidationException;

new #[Layout('layouts.app')] class extends Component {
    public Medicine $medicine;
    public bool $isMasterDataReadOnly = false;

    // Form fields
    public string $name = '';
    public string $generic_name = '';
    public ?int $category_id = null;
    public ?float $concentration_value = null;
    public ?int $concentration_unit_id = null;
    public ?int $container_id = null;
    public ?int $content_quantity = null;
    public ?int $content_unit_id = null;
    public ?int $laboratory_id = null;
    public ?int $sanitary_registry_id = null;
    public bool $is_cold_chain = false;
    public bool $is_special_control = false;
    public int $min_stock = 0;
    public ?float $selling_price = null;
    public string $description = '';

    // Barcodes list state
    // Format: [['id' => int|null, 'barcode' => string, 'is_new' => bool, 'is_main' => bool]]
    public array $barcodes = [];
    public string $newBarcode = '';

    // Search and select laboratory state
    public string $laboratorySearch = '';
    public bool $showLaboratoriesDropdown = false;

    // Search and select sanitary registry state
    public string $sanitaryRegistrySearch = '';
    public bool $showSanitaryRegistriesDropdown = false;

    // Quick laboratory creation state
    public string $quickLabName = '';
    public string $quickLabDescription = '';

    // Quick sanitary registry creation state
    public string $quickRegistryNumber = '';
    public ?int $quickRegistryLabId = null;
    public string $quickRegistryExpirationDate = '';
    public string $quickRegistryStatus = 'valid';
    public string $quickRegistryDescription = '';

    public function mount(Medicine $medicine): void
    {
        $this->medicine = $medicine;
        $this->isMasterDataReadOnly = $medicine->hasLotsOrSales();

        $this->name = $medicine->name;
        $this->generic_name = $medicine->generic_name ?? '';
        $this->category_id = $medicine->category_id;
        $this->concentration_value = (float) $medicine->concentration_value;
        $this->concentration_unit_id = $medicine->concentration_unit_id;
        $this->container_id = $medicine->container_id;
        $this->content_quantity = $medicine->content_quantity;
        $this->content_unit_id = $medicine->content_unit_id;
        $this->laboratory_id = $medicine->laboratory_id;
        $this->sanitary_registry_id = $medicine->sanitary_registry_id;
        $this->is_cold_chain = (bool) $medicine->is_cold_chain;
        $this->is_special_control = (bool) $medicine->is_special_control;
        $this->min_stock = $medicine->min_stock;
        $this->selling_price = (float) $medicine->selling_price;
        $this->description = $medicine->description ?? '';

        $this->laboratorySearch = $medicine->laboratory?->name ?? '';
        $this->sanitaryRegistrySearch = $medicine->sanitaryRegistry?->registration_number ?? '';

        // Load barcodes
        $this->barcodes = $medicine->barcodes()->get()->map(function ($b) {
            return [
                'id' => $b->id,
                'barcode' => $b->barcode,
                'is_main' => (bool) $b->is_main,
                'is_new' => false,
            ];
        })->toArray();
    }

    protected function rules(): array
    {
        if ($this->isMasterDataReadOnly) {
            return [
                'category_id' => ['required', 'exists:categories,id'],
                'selling_price' => ['required', 'numeric', 'min:0'],
                'min_stock' => ['required', 'integer', 'min:0'],
                'description' => ['nullable', 'string', 'max:500'],
                'is_cold_chain' => ['boolean'],
                'is_special_control' => ['boolean'],
            ];
        }

        return [
            'name' => ['required', 'string', 'max:100'],
            'generic_name' => ['nullable', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'concentration_value' => ['required', 'numeric', 'min:0'],
            'concentration_unit_id' => ['required', 'exists:concentration_units,id'],
            'container_id' => ['required', 'exists:containers,id'],
            'content_quantity' => ['required', 'integer', 'min:1'],
            'content_unit_id' => ['required', 'exists:content_units,id'],
            'laboratory_id' => ['required', 'exists:laboratories,id'],
            'sanitary_registry_id' => ['required', 'exists:sanitary_registries,id'],
            'is_cold_chain' => ['boolean'],
            'is_special_control' => ['boolean'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function messages(): array
    {
        return [
            'name.required' => 'El nombre comercial es obligatorio.',
            'name.max' => 'El nombre comercial no debe exceder los 100 caracteres.',
            'generic_name.max' => 'El nombre genérico no debe exceder los 255 caracteres.',
            'category_id.required' => 'La categoría es obligatoria.',
            'category_id.exists' => 'La categoría seleccionada no es válida.',
            'concentration_value.required' => 'El valor de concentración es obligatorio.',
            'concentration_value.numeric' => 'El valor de concentración debe ser un número.',
            'concentration_value.min' => 'El valor de concentración debe ser mayor o igual a 0.',
            'concentration_unit_id.required' => 'La unidad de concentración es obligatoria.',
            'concentration_unit_id.exists' => 'La unidad de concentración seleccionada no es válida.',
            'container_id.required' => 'El contenedor es obligatorio.',
            'container_id.exists' => 'El contenedor seleccionado no es válido.',
            'content_quantity.required' => 'La cantidad de contenido es obligatoria.',
            'content_quantity.integer' => 'La cantidad de contenido debe ser un número entero.',
            'content_quantity.min' => 'La cantidad de contenido debe ser al menos 1.',
            'content_unit_id.required' => 'La unidad de contenido es obligatoria.',
            'content_unit_id.exists' => 'La unidad de contenido seleccionada no es válida.',
            'laboratory_id.required' => 'El laboratorio es obligatorio.',
            'laboratory_id.exists' => 'El laboratorio seleccionado no es válido.',
            'sanitary_registry_id.required' => 'El registro sanitario es obligatorio.',
            'sanitary_registry_id.exists' => 'El registro sanitario seleccionado no es válido.',
            'min_stock.required' => 'El stock mínimo es obligatorio.',
            'min_stock.integer' => 'El stock mínimo debe ser un número entero.',
            'min_stock.min' => 'El stock mínimo debe ser mayor o igual a 0.',
            'selling_price.required' => 'El precio de venta es obligatorio.',
            'selling_price.numeric' => 'El precio de venta debe ser un número.',
            'selling_price.min' => 'El precio de venta debe ser mayor o igual a 0.',
            'description.max' => 'La descripción no debe exceder los 500 caracteres.',
        ];
    }

    public function updatedCategoryId(int|string|null $value): void
    {
        if ($value) {
            $category = Category::find($value);
            if ($category) {
                $this->is_cold_chain = (bool) $category->is_cold_chain;
                $this->is_special_control = (bool) $category->is_special_control;
            }
        }
    }

    public function updatedLaboratorySearch(): void
    {
        if ($this->isMasterDataReadOnly) {
            return;
        }
        $this->laboratory_id = null;
        $this->showLaboratoriesDropdown = true;
    }

    public function selectLaboratory(int $id, string $name): void
    {
        if ($this->isMasterDataReadOnly) {
            return;
        }
        $this->laboratory_id = $id;
        $this->laboratorySearch = $name;
        $this->showLaboratoriesDropdown = false;
        $this->resetErrorBag('laboratory_id');
    }

    public function openQuickLabModal(): void
    {
        if ($this->isMasterDataReadOnly) {
            return;
        }
        $this->quickLabName = trim($this->laboratorySearch);
        $this->quickLabDescription = '';
        $this->resetErrorBag(['quickLabName', 'quickLabDescription']);
        $this->showLaboratoriesDropdown = false;
        $this->dispatch('open-modal', 'quick-laboratory-modal');
    }

    public function saveQuickLaboratory(LaboratoryService $laboratoryService): void
    {
        $this->validate([
            'quickLabName' => ['required', 'string', 'max:255', 'unique:laboratories,name'],
            'quickLabDescription' => ['nullable', 'string', 'max:255'],
        ], [
            'quickLabName.required' => 'El nombre del laboratorio es obligatorio.',
            'quickLabName.unique' => 'Este laboratorio ya se encuentra registrado.',
            'quickLabName.max' => 'El nombre del laboratorio no debe exceder los 255 caracteres.',
            'quickLabDescription.max' => 'La descripción no debe exceder los 255 caracteres.',
        ]);

        $laboratory = $laboratoryService->create([
            'name' => $this->quickLabName,
            'description' => $this->quickLabDescription,
        ]);

        $this->selectLaboratory($laboratory->id, $laboratory->name);
        $this->dispatch('close-modal', 'quick-laboratory-modal');
        $this->reset(['quickLabName', 'quickLabDescription']);

        session()->flash('success_quick_lab', 'El laboratorio ha sido registrado y seleccionado con éxito.');
    }

    public function updatedSanitaryRegistrySearch(): void
    {
        if ($this->isMasterDataReadOnly) {
            return;
        }
        $this->sanitary_registry_id = null;
        $this->showSanitaryRegistriesDropdown = true;
    }

    public function selectSanitaryRegistry(int $id, string $number): void
    {
        if ($this->isMasterDataReadOnly) {
            return;
        }
        $this->sanitary_registry_id = $id;
        $this->sanitaryRegistrySearch = $number;
        $this->showSanitaryRegistriesDropdown = false;
        $this->resetErrorBag('sanitary_registry_id');
    }

    public function openQuickRegistryModal(): void
    {
        if ($this->isMasterDataReadOnly) {
            return;
        }
        $this->quickRegistryNumber = trim($this->sanitaryRegistrySearch);
        $this->quickRegistryLabId = $this->laboratory_id;
        $this->quickRegistryExpirationDate = '';
        $this->quickRegistryStatus = 'valid';
        $this->quickRegistryDescription = '';
        $this->resetErrorBag(['quickRegistryNumber', 'quickRegistryLabId', 'quickRegistryExpirationDate', 'quickRegistryStatus', 'quickRegistryDescription']);
        $this->showSanitaryRegistriesDropdown = false;
        $this->dispatch('open-modal', 'quick-registry-modal');
    }

    public function saveQuickSanitaryRegistry(SanitaryRegistryService $registryService): void
    {
        $this->validate([
            'quickRegistryNumber' => [
                'required',
                'string',
                'max:50',
                'unique:sanitary_registries,registration_number',
                'regex:/^INVIMA\s+\d{4}[A-Z]-\d{7}$/i',
            ],
            'quickRegistryLabId' => ['required', 'integer', 'exists:laboratories,id'],
            'quickRegistryExpirationDate' => ['required', 'date'],
            'quickRegistryStatus' => ['required', 'string', 'in:valid,expired,under_renewal'],
            'quickRegistryDescription' => ['nullable', 'string', 'max:65535'],
        ], [
            'quickRegistryNumber.required' => 'El número de registro sanitario es obligatorio.',
            'quickRegistryNumber.unique' => 'Este número de registro sanitario ya se encuentra registrado.',
            'quickRegistryNumber.max' => 'El número de registro sanitario no debe exceder los 50 caracteres.',
            'quickRegistryNumber.regex' => 'El formato del registro sanitario es inválido. Debe ser como INVIMA 2026M-1234567.',
            'quickRegistryLabId.required' => 'El laboratorio fabricante es obligatorio.',
            'quickRegistryLabId.exists' => 'El laboratorio seleccionado no es válido.',
            'quickRegistryExpirationDate.required' => 'La fecha de vencimiento es obligatoria.',
            'quickRegistryStatus.required' => 'El estado es obligatorio.',
            'quickRegistryStatus.in' => 'El estado seleccionado no es válido.',
        ]);

        $registry = $registryService->create([
            'registration_number' => $this->quickRegistryNumber,
            'laboratory_id' => $this->quickRegistryLabId,
            'expiration_date' => $this->quickRegistryExpirationDate,
            'status' => $this->quickRegistryStatus,
            'description' => $this->quickRegistryDescription,
        ]);

        $this->selectSanitaryRegistry($registry->id, $registry->registration_number);
        $this->dispatch('close-modal', 'quick-registry-modal');
        $this->reset(['quickRegistryNumber', 'quickRegistryLabId', 'quickRegistryExpirationDate', 'quickRegistryStatus', 'quickRegistryDescription']);

        session()->flash('success_quick_registry', 'El registro sanitario ha sido registrado y seleccionado con éxito.');
    }

    public function addBarcode(): void
    {
        $barcodeVal = trim($this->newBarcode);
        if (empty($barcodeVal)) {
            $this->addError('newBarcode', 'El código de barras es obligatorio.');
            return;
        }

        if (! preg_match('/^[0-9]+$/', $barcodeVal)) {
            $this->addError('newBarcode', 'El código de barras debe estar compuesto únicamente por números.');
            return;
        }
        $len = strlen($barcodeVal);
        if ($len < 8 || $len > 14) {
            $this->addError('newBarcode', 'El código de barras debe tener entre 8 y 14 dígitos.');
            return;
        }

        // Unique in current component state
        foreach ($this->barcodes as $b) {
            if ($b['barcode'] === $barcodeVal) {
                $this->addError('newBarcode', 'Este código de barras ya está en la lista.');
                return;
            }
        }

        // Unique in system
        if (MedicineBarcode::where('barcode', $barcodeVal)->exists()) {
            $this->addError('newBarcode', 'Este código de barras ya se encuentra registrado.');
            return;
        }

        $this->barcodes[] = [
            'id' => null,
            'barcode' => $barcodeVal,
            'is_main' => count($this->barcodes) === 0,
            'is_new' => true,
        ];

        $this->newBarcode = '';
        $this->resetErrorBag('newBarcode');
    }

    public function removeBarcode(int $index): void
    {
        $barcodeItem = $this->barcodes[$index];
        if (!$barcodeItem['is_new'] && $this->isMasterDataReadOnly) {
            $this->addError('barcodes', 'No se pueden eliminar códigos de barras de un medicamento con movimientos de inventario.');
            return;
        }

        array_splice($this->barcodes, $index, 1);
        $this->resetErrorBag('barcodes');
    }

    public function save(MedicineService $medicineService): void
    {
        $validated = $this->validate();

        // Enforce barcodes not empty
        if (empty($this->barcodes)) {
            $this->addError('barcodes', 'Debe haber al menos un código de barras asociado al medicamento.');
            return;
        }

        try {
            $medicineService->update($this->medicine, $validated, $this->barcodes);
            session()->flash('success', 'El medicamento ha sido actualizado con éxito.');
            $this->redirect(route('medicines.index'), navigate: true);
        } catch (ValidationException $e) {
            foreach ($e->errors() as $key => $messages) {
                foreach ($messages as $msg) {
                    $this->addError($key, $msg);
                }
            }
        }
    }

    public function with(): array
    {
        $categories = Category::orderBy('name')->get();
        $concentrationUnits = ConcentrationUnit::orderBy('name')->get();
        $containers = Container::orderBy('name')->get();
        $contentUnits = ContentUnit::orderBy('name')->get();

        $laboratories = [];
        if ($this->showLaboratoriesDropdown) {
            $laboratories = Laboratory::query()
                ->where('name', 'like', '%' . trim($this->laboratorySearch) . '%')
                ->orderBy('name')
                ->take(10)
                ->get();
        }

        $registries = [];
        if ($this->showSanitaryRegistriesDropdown) {
            $registries = SanitaryRegistry::query()
                ->where('status', 'valid')
                ->where('registration_number', 'like', '%' . trim($this->sanitaryRegistrySearch) . '%')
                ->orderBy('registration_number')
                ->take(10)
                ->get();
        }

        $allLaboratories = Laboratory::orderBy('name')->get();

        return [
            'categories' => $categories,
            'concentrationUnits' => $concentrationUnits,
            'containers' => $containers,
            'contentUnits' => $contentUnits,
            'filteredLaboratories' => $laboratories,
            'filteredSanitaryRegistries' => $registries,
            'allLaboratories' => $allLaboratories,
        ];
    }
}; ?>

<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8"
     x-data="{ 
         labDropdown: @entangle('showLaboratoriesDropdown'),
         regDropdown: @entangle('showSanitaryRegistriesDropdown')
     }">
     
    <!-- Header/Back Navigation -->
    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('medicines.index') }}" wire:navigate
            class="inline-flex items-center text-sm font-medium text-slate-600 hover:text-blue-900 transition-colors gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"></path>
            </svg>
            <span>Volver a Medicamentos</span>
        </a>
    </div>

    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-blue-900 dark:text-white">Editar Medicamento</h1>
        <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
            Modifica la información operativa y los códigos de barras. Los datos maestros esenciales estarán en modo solo lectura si el producto tiene movimientos de inventario registrados.
        </p>
    </div>

    <!-- Alerts Quick Registration Success -->
    @if (session()->has('success_quick_lab'))
        <div class="mb-4 p-4 bg-lime-50 border border-lime-200 text-lime-800 rounded-xl flex items-center shadow-sm" role="alert">
            <svg class="w-5 h-5 mr-2 text-lime-600 shrink-0" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            <span class="font-medium">{{ session('success_quick_lab') }}</span>
        </div>
    @endif

    @if (session()->has('success_quick_registry'))
        <div class="mb-4 p-4 bg-lime-50 border border-lime-200 text-lime-800 rounded-xl flex items-center shadow-sm" role="alert">
            <svg class="w-5 h-5 mr-2 text-lime-600 shrink-0" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            <span class="font-medium">{{ session('success_quick_registry') }}</span>
        </div>
    @endif

    <!-- Indicator of locked state -->
    @if ($isMasterDataReadOnly)
        <div class="mb-6 p-4 bg-amber-50 border border-amber-200 text-amber-800 rounded-xl flex items-start gap-2.5 shadow-sm" id="master-data-lock-banner">
            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"></path>
            </svg>
            <div>
                <span class="font-bold">Datos Maestros Protegidos (Solo Lectura):</span>
                <p class="text-xs text-amber-700 mt-1 font-normal">
                    Este medicamento ya tiene lotes o movimientos de venta asociados en el inventario. Los campos de Nombre Comercial, Genérico, Concentración, Presentación, Laboratorio y Registro Sanitario se han bloqueado para resguardar la integridad histórica.
                </p>
            </div>
        </div>
    @endif

    <!-- Card Form -->
    <div class="bg-white border border-slate-100 shadow-sm rounded-2xl p-6 md:p-8">
        <form wire:submit="save" class="space-y-6">
            
            <!-- Section: Identificación Básica -->
            <div class="border-b border-slate-100 pb-4">
                <h3 class="text-md font-bold text-blue-900 mb-4">Identificación del Producto</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Commercial Name -->
                    <div>
                        <x-input-label for="name" value="Nombre Comercial" class="text-blue-900 font-semibold mb-1" />
                        <x-text-input wire:model="name" id="name" type="text" class="block w-full mt-1 disabled:bg-slate-100 disabled:text-slate-500" placeholder="Ej. Acetaminofén MK" @disabled($isMasterDataReadOnly) required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <!-- Generic Name -->
                    <div>
                        <x-input-label for="generic_name" value="Nombre Genérico / Principio Activo" class="text-blue-900 font-semibold mb-1" />
                        <x-text-input wire:model="generic_name" id="generic_name" type="text" class="block w-full mt-1 disabled:bg-slate-100 disabled:text-slate-500" placeholder="Ej. Acetaminofén" @disabled($isMasterDataReadOnly) />
                        <x-input-error :messages="$errors->get('generic_name')" class="mt-2" />
                    </div>

                    <!-- Category (Always Editable) -->
                    <div>
                        <x-input-label for="category_id" value="Categoría" class="text-blue-900 font-semibold mb-1" />
                        <select wire:model.live="category_id" id="category_id" 
                            class="border-slate-100 bg-slate-50 text-blue-900 focus:border-lime-500 focus:ring-lime-500 rounded-md shadow-sm block w-full mt-1 p-3 text-sm cursor-pointer" required>
                            <option value="">Seleccione una categoría...</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                    </div>
                </div>
            </div>

            <!-- Section: Barcodes Manager -->
            <div class="border-b border-slate-100 pb-4">
                <h3 class="text-md font-bold text-blue-900 mb-4">Gestor de Códigos de Barras</h3>
                
                <div class="space-y-4">
                    <!-- Add Barcode Subform -->
                    <div class="flex flex-col sm:flex-row gap-3 items-start">
                        <div class="w-full sm:max-w-md">
                            <x-text-input wire:model="newBarcode" id="newBarcode" type="text" class="block w-full" placeholder="Escriba o escanee un nuevo código..." />
                            <x-input-error :messages="$errors->get('newBarcode')" class="mt-2" />
                        </div>
                        <button type="button" wire:click="addBarcode" id="add-barcode-button"
                            class="px-4 py-2.5 bg-blue-900 hover:bg-lime-500 hover:text-blue-950 text-white text-sm font-semibold rounded-lg shadow-sm transition cursor-pointer shrink-0">
                            Añadir Código
                        </button>
                    </div>

                    <x-input-error :messages="$errors->get('barcodes')" class="mt-2" />

                    <!-- Barcodes list table -->
                    <div class="bg-slate-50 border border-slate-100 rounded-xl overflow-hidden mt-4">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead class="bg-slate-100/70">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-blue-900 uppercase">Código</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-blue-900 uppercase">Estado</th>
                                    <th class="px-4 py-3 text-right text-xs font-bold text-blue-900 uppercase">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 bg-white">
                                @foreach($barcodes as $idx => $b)
                                    <tr class="hover:bg-slate-50/50" wire:key="barcode-row-{{ $idx }}">
                                        <td class="px-4 py-2">
                                            <input type="text" wire:model="barcodes.{{ $idx }}.barcode" 
                                                class="w-full max-w-xs border-slate-200 focus:border-lime-500 focus:ring-lime-500 rounded-md shadow-sm p-1.5 text-sm disabled:bg-slate-100 disabled:text-slate-500" 
                                                @disabled(!($b['is_new'] || !$isMasterDataReadOnly))>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-xs">
                                            @if($b['is_main'])
                                                <span class="inline-flex items-center px-2 py-0.5 rounded font-bold bg-blue-50 text-blue-800 border border-blue-200">Principal</span>
                                            @endif
                                            @if($b['is_new'])
                                                <span class="inline-flex items-center px-2 py-0.5 rounded font-bold bg-lime-50 text-lime-800 border border-lime-200">Nuevo</span>
                                            @endif
                                            @if(!$b['is_new'] && !$b['is_main'])
                                                <span class="inline-flex items-center px-2 py-0.5 rounded font-medium bg-slate-100 text-slate-800">Vinculado</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-right text-sm">
                                            @if($b['is_new'] || !$isMasterDataReadOnly)
                                                <button type="button" wire:click="removeBarcode({{ $idx }})"
                                                    class="text-red-600 hover:text-red-950 inline-flex items-center gap-1 cursor-pointer font-medium btn-remove-barcode"
                                                    title="Eliminar código de barras">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"></path>
                                                    </svg>
                                                    <span>Quitar</span>
                                                </button>
                                            @else
                                                <span class="text-xs text-slate-400 italic">No editable</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Section: Especificaciones Técnicas y Presentación -->
            <div class="border-b border-slate-100 pb-4">
                <h3 class="text-md font-bold text-blue-900 mb-4">Potencia y Presentación</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Concentration Value -->
                    <div>
                        <x-input-label for="concentration_value" value="Valor de Concentración" class="text-blue-900 font-semibold mb-1" />
                        <x-text-input wire:model="concentration_value" id="concentration_value" type="number" step="0.01" class="block w-full mt-1 disabled:bg-slate-100 disabled:text-slate-500" placeholder="Ej. 500" @disabled($isMasterDataReadOnly) required />
                        <x-input-error :messages="$errors->get('concentration_value')" class="mt-2" />
                    </div>

                    <!-- Concentration Unit -->
                    <div>
                        <x-input-label for="concentration_unit_id" value="Unidad de Concentración" class="text-blue-900 font-semibold mb-1" />
                        <select wire:model="concentration_unit_id" id="concentration_unit_id" 
                            class="border-slate-100 bg-slate-50 text-blue-900 focus:border-lime-500 focus:ring-lime-500 rounded-md shadow-sm block w-full mt-1 p-3 text-sm cursor-pointer disabled:bg-slate-100 disabled:text-slate-500" @disabled($isMasterDataReadOnly) required>
                            <option value="">Seleccione unidad...</option>
                            @foreach($concentrationUnits as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->name }} ({{ $unit->symbol }})</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('concentration_unit_id')" class="mt-2" />
                    </div>

                    <!-- Container -->
                    <div>
                        <x-input-label for="container_id" value="Contenedor" class="text-blue-900 font-semibold mb-1" />
                        <select wire:model="container_id" id="container_id" 
                            class="border-slate-100 bg-slate-50 text-blue-900 focus:border-lime-500 focus:ring-lime-500 rounded-md shadow-sm block w-full mt-1 p-3 text-sm cursor-pointer disabled:bg-slate-100 disabled:text-slate-500" @disabled($isMasterDataReadOnly) required>
                            <option value="">Seleccione contenedor...</option>
                            @foreach($containers as $container)
                                <option value="{{ $container->id }}">{{ $container->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('container_id')" class="mt-2" />
                    </div>

                    <!-- Content Quantity -->
                    <div>
                        <x-input-label for="content_quantity" value="Cantidad de Contenido" class="text-blue-900 font-semibold mb-1" />
                        <x-text-input wire:model="content_quantity" id="content_quantity" type="number" class="block w-full mt-1 disabled:bg-slate-100 disabled:text-slate-500" placeholder="Ej. 30" @disabled($isMasterDataReadOnly) required />
                        <x-input-error :messages="$errors->get('content_quantity')" class="mt-2" />
                    </div>

                    <!-- Content Unit -->
                    <div>
                        <x-input-label for="content_unit_id" value="Unidad de Contenido" class="text-blue-900 font-semibold mb-1" />
                        <select wire:model="content_unit_id" id="content_unit_id" 
                            class="border-slate-100 bg-slate-50 text-blue-900 focus:border-lime-500 focus:ring-lime-500 rounded-md shadow-sm block w-full mt-1 p-3 text-sm cursor-pointer disabled:bg-slate-100 disabled:text-slate-500" @disabled($isMasterDataReadOnly) required>
                            <option value="">Seleccione unidad...</option>
                            @foreach($contentUnits as $unit)
                                <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('content_unit_id')" class="mt-2" />
                    </div>
                </div>
            </div>

            <!-- Section: Relaciones y Trazabilidad -->
            <div class="border-b border-slate-100 pb-4">
                <h3 class="text-md font-bold text-blue-900 mb-4">Relaciones y Trazabilidad</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Searchable Laboratory Selection -->
                    <div class="relative" @click.away="labDropdown = false">
                        <x-input-label for="laboratorySearch" value="Laboratorio" class="text-blue-900 font-semibold mb-1" />
                        <div class="relative">
                            <x-text-input wire:model.live="laboratorySearch" id="laboratorySearch" type="text" 
                                class="block w-full mt-1 disabled:bg-slate-100 disabled:text-slate-500" 
                                placeholder="Escriba para buscar laboratorio..." 
                                @focus="labDropdown = true" 
                                @disabled($isMasterDataReadOnly) required />
                            
                            @if($laboratory_id && !$isMasterDataReadOnly)
                                <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-lime-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </span>
                            @endif
                        </div>

                        <!-- Dropdown List -->
                        @if($showLaboratoriesDropdown && !$isMasterDataReadOnly)
                            <div class="absolute z-10 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden">
                                @if(count($filteredLaboratories) > 0)
                                    <ul class="divide-y divide-slate-100 max-h-48 overflow-y-auto">
                                        @foreach($filteredLaboratories as $lab)
                                            <li>
                                                <button type="button" wire:click="selectLaboratory({{ $lab->id }}, '{{ addslashes($lab->name) }}')"
                                                    class="w-full px-4 py-3 text-left text-sm text-slate-700 hover:bg-slate-50 transition-colors font-medium">
                                                    {{ $lab->name }}
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <div class="p-4 text-center text-sm text-slate-500">
                                        No se encontraron laboratorios.
                                    </div>
                                @endif

                                <div class="px-4 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                                    <span class="text-xs text-slate-500">¿No encuentras el laboratorio?</span>
                                    <button type="button" wire:click="openQuickLabModal" 
                                        class="text-xs font-bold text-blue-900 hover:text-lime-600 transition-colors cursor-pointer">
                                        + Registrar Nuevo
                                    </button>
                                </div>
                            </div>
                        @endif
                        
                        <input type="hidden" wire:model="laboratory_id">
                        <x-input-error :messages="$errors->get('laboratory_id')" class="mt-2" />
                    </div>

                    <!-- Searchable Sanitary Registry Selection -->
                    <div class="relative" @click.away="regDropdown = false">
                        <x-input-label for="sanitaryRegistrySearch" value="Registro Sanitario (INVIMA)" class="text-blue-900 font-semibold mb-1" />
                        <div class="relative">
                            <x-text-input wire:model.live="sanitaryRegistrySearch" id="sanitaryRegistrySearch" type="text" 
                                class="block w-full mt-1 disabled:bg-slate-100 disabled:text-slate-500" 
                                placeholder="Escriba para buscar registro INVIMA..." 
                                @focus="regDropdown = true" 
                                @disabled($isMasterDataReadOnly) required />
                            
                            @if($sanitary_registry_id && !$isMasterDataReadOnly)
                                <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-lime-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </span>
                            @endif
                        </div>

                        <!-- Dropdown List -->
                        @if($showSanitaryRegistriesDropdown && !$isMasterDataReadOnly)
                            <div class="absolute z-10 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg overflow-hidden">
                                @if(count($filteredSanitaryRegistries) > 0)
                                    <ul class="divide-y divide-slate-100 max-h-48 overflow-y-auto">
                                        @foreach($filteredSanitaryRegistries as $reg)
                                            <li>
                                                <button type="button" wire:click="selectSanitaryRegistry({{ $reg->id }}, '{{ addslashes($reg->registration_number) }}')"
                                                    class="w-full px-4 py-3 text-left text-sm text-slate-700 hover:bg-slate-50 transition-colors font-medium">
                                                    {{ $reg->registration_number }}
                                                </button>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <div class="p-4 text-center text-sm text-slate-500">
                                        No se encontraron registros sanitarios vigentes.
                                    </div>
                                @endif

                                <div class="px-4 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-between">
                                    <span class="text-xs text-slate-500">¿No encuentras el registro sanitario?</span>
                                    <button type="button" wire:click="openQuickRegistryModal" 
                                        class="text-xs font-bold text-blue-900 hover:text-lime-600 transition-colors cursor-pointer">
                                        + Registrar Nuevo
                                    </button>
                                </div>
                            </div>
                        @endif
                        
                        <input type="hidden" wire:model="sanitary_registry_id">
                        <x-input-error :messages="$errors->get('sanitary_registry_id')" class="mt-2" />
                    </div>
                </div>
            </div>

            <!-- Section: Parámetros Logísticos y Precios -->
            <div class="border-b border-slate-100 pb-4">
                <h3 class="text-md font-bold text-blue-900 mb-4">Logística y Precios</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Selling Price -->
                    <div>
                        <x-input-label for="selling_price" value="Precio de Venta" class="text-blue-900 font-semibold mb-1" />
                        <x-text-input wire:model="selling_price" id="selling_price" type="number" step="0.01" class="block w-full mt-1" placeholder="Ej. 15000.50" required />
                        <x-input-error :messages="$errors->get('selling_price')" class="mt-2" />
                    </div>

                    <!-- Minimum Stock -->
                    <div>
                        <x-input-label for="min_stock" value="Stock Mínimo" class="text-blue-900 font-semibold mb-1" />
                        <x-text-input wire:model="min_stock" id="min_stock" type="number" class="block w-full mt-1" placeholder="Ej. 10" required />
                        <x-input-error :messages="$errors->get('min_stock')" class="mt-2" />
                    </div>

                    <!-- Logical Flags -->
                    <div class="bg-slate-50 border border-slate-100/50 rounded-xl p-4 md:p-6 space-y-4 md:col-span-2">
                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input wire:model="is_cold_chain" id="is_cold_chain" type="checkbox" 
                                    class="w-4 h-4 rounded bg-slate-100 border-slate-200 text-blue-900 shadow-sm focus:ring-lime-500">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="is_cold_chain" class="font-medium text-blue-900">Requiere cadena de frío</label>
                                <p class="text-xs text-slate-600">Refrigerado (2°C - 8°C).</p>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="flex items-center h-5">
                                <input wire:model="is_special_control" id="is_special_control" type="checkbox" 
                                    class="w-4 h-4 rounded bg-slate-100 border-slate-200 text-blue-900 shadow-sm focus:ring-lime-500">
                            </div>
                            <div class="ml-3 text-sm">
                                <label for="is_special_control" class="font-medium text-blue-900">Sustancia de control especial</label>
                                <p class="text-xs text-slate-600">Regulado por el Fondo Nacional de Estupefacientes.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div>
                <x-input-label for="description" value="Descripción / Notas Adicionales (Opcional)" class="text-blue-900 font-semibold mb-1" />
                <textarea wire:model="description" id="description" rows="3" 
                    class="border-slate-100 bg-slate-50 text-blue-900 focus:border-lime-500 focus:ring-lime-500 rounded-md shadow-sm block w-full mt-1 p-3 text-sm" 
                    placeholder="Agregue observaciones o advertencias sanitarias..."></textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>

            <!-- Submit buttons -->
            <div class="flex items-center justify-end space-x-4 pt-4 border-t border-slate-100">
                <a href="{{ route('medicines.index') }}" wire:navigate class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-800 transition-colors">
                    Cancelar
                </a>
                <x-primary-button class="bg-blue-900 hover:bg-lime-500 hover:text-blue-950 text-white font-medium px-6 py-2.5 rounded-lg shadow-sm transition duration-150 ease-in-out cursor-pointer" id="btn-save-medicine">
                    Guardar Cambios
                </x-primary-button>
            </div>
        </form>
    </div>

    <!-- Quick Laboratory Creation Modal -->
    <x-modal name="quick-laboratory-modal" focusable>
        <div class="p-6">
            <h2 class="text-lg font-bold text-blue-900">
                Registro Rápido de Laboratorio
            </h2>
            <form wire:submit.prevent="saveQuickLaboratory" class="mt-4 space-y-4">
                <div>
                    <x-input-label for="quickLabName" value="Nombre del Laboratorio" class="text-blue-900 font-semibold mb-1" />
                    <x-text-input wire:model="quickLabName" id="quickLabName" type="text" class="block w-full mt-1" required />
                    <x-input-error :messages="$errors->get('quickLabName')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="quickLabDescription" value="Descripción (Opcional)" class="text-blue-900 font-semibold mb-1" />
                    <textarea wire:model="quickLabDescription" id="quickLabDescription" rows="3" 
                        class="border-slate-100 bg-slate-50 text-blue-900 focus:border-lime-500 focus:ring-lime-500 rounded-md shadow-sm block w-full mt-1 p-3 text-sm"></textarea>
                    <x-input-error :messages="$errors->get('quickLabDescription')" class="mt-1" />
                </div>
                <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4">
                    <x-secondary-button x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                    <x-primary-button type="submit" class="bg-blue-900">Registrar y Seleccionar</x-primary-button>
                </div>
            </form>
        </div>
    </x-modal>

    <!-- Quick Sanitary Registry Creation Modal -->
    <x-modal name="quick-registry-modal" focusable>
        <div class="p-6">
            <h2 class="text-lg font-bold text-blue-900">
                Registro Rápido de Registro Sanitario
            </h2>
            <form wire:submit.prevent="saveQuickSanitaryRegistry" class="mt-4 space-y-4">
                <div>
                    <x-input-label for="quickRegistryNumber" value="Número de Registro Sanitario" class="text-blue-900 font-semibold mb-1" />
                    <x-text-input wire:model="quickRegistryNumber" id="quickRegistryNumber" type="text" class="block w-full mt-1 uppercase" placeholder="Ej. INVIMA 2026M-1234567" required />
                    <x-input-error :messages="$errors->get('quickRegistryNumber')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="quickRegistryLabId" value="Laboratorio Fabricante" class="text-blue-900 font-semibold mb-1" />
                    <select wire:model="quickRegistryLabId" id="quickRegistryLabId" class="block w-full mt-1 cursor-pointer" required>
                        <option value="">Seleccione laboratorio...</option>
                        @foreach($allLaboratories as $lab)
                            <option value="{{ $lab->id }}">{{ $lab->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('quickRegistryLabId')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="quickRegistryExpirationDate" value="Fecha de Vencimiento" class="text-blue-900 font-semibold mb-1" />
                    <x-text-input wire:model="quickRegistryExpirationDate" id="quickRegistryExpirationDate" type="date" class="block w-full mt-1" required />
                    <x-input-error :messages="$errors->get('quickRegistryExpirationDate')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="quickRegistryStatus" value="Estado" class="text-blue-900 font-semibold mb-1" />
                    <select wire:model="quickRegistryStatus" id="quickRegistryStatus" class="block w-full mt-1 cursor-pointer" required>
                        <option value="valid">Vigente</option>
                        <option value="expired">Vencido</option>
                        <option value="under_renewal">En renovación</option>
                    </select>
                    <x-input-error :messages="$errors->get('quickRegistryStatus')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="quickRegistryDescription" value="Descripción (Opcional)" class="text-blue-900 font-semibold mb-1" />
                    <textarea wire:model="quickRegistryDescription" id="quickRegistryDescription" rows="2" class="block w-full mt-1"></textarea>
                    <x-input-error :messages="$errors->get('quickRegistryDescription')" class="mt-1" />
                </div>
                <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4">
                    <x-secondary-button x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                    <x-primary-button type="submit" class="bg-blue-900">Registrar y Seleccionar</x-primary-button>
                </div>
            </form>
        </div>
    </x-modal>
</div>

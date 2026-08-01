<?php

declare(strict_types=1);

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Category;
use App\Models\ConcentrationUnit;
use App\Models\Container;
use App\Models\ContentUnit;
use App\Models\Laboratory;
use App\Models\SanitaryRegistry;
use App\Services\MedicineService;
use App\Services\LaboratoryService;
use App\Services\SanitaryRegistryService;
use App\Http\Requests\Medicine\StoreMedicineRequest;
use Illuminate\Validation\ValidationException;

new #[Layout('layouts.app')] class extends Component {
    public string $barcode = '';
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
    public int $min_stock = 5;
    public ?float $selling_price = null;
    public string $description = '';

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

    // Duplicate detection modal state
    public ?int $duplicateMedicineId = null;
    public string $duplicateMedicineName = '';
    public string $duplicateMedicinePresentation = '';

    /**
     * Get the validation rules.
     */
    protected function rules(): array
    {
        return (new StoreMedicineRequest())->rules();
    }

    /**
     * Get the custom validation error messages.
     */
    protected function messages(): array
    {
        return (new StoreMedicineRequest())->messages();
    }

    /**
     * Triggered when a category is selected. Updates cold chain and special control flags based on defaults.
     */
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

    /**
     * Generate a unique internal barcode.
     */
    public function generateInternalBarcode(): void
    {
        do {
            $code = '999' . str_pad((string) random_int(0, 999999999), 9, '0', STR_PAD_LEFT);
        } while (\App\Models\MedicineBarcode::where('barcode', $code)->exists());

        $this->barcode = $code;
        $this->resetErrorBag('barcode');
    }

    /**
     * Search laboratory handlers.
     */
    public function updatedLaboratorySearch(): void
    {
        $this->laboratory_id = null;
        $this->showLaboratoriesDropdown = true;
    }

    public function selectLaboratory(int $id, string $name): void
    {
        $this->laboratory_id = $id;
        $this->laboratorySearch = $name;
        $this->showLaboratoriesDropdown = false;
        $this->resetErrorBag('laboratory_id');
    }

    public function openQuickLabModal(): void
    {
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

    /**
     * Search sanitary registry handlers.
     */
    public function updatedSanitaryRegistrySearch(): void
    {
        $this->sanitary_registry_id = null;
        $this->showSanitaryRegistriesDropdown = true;
    }

    public function selectSanitaryRegistry(int $id, string $number): void
    {
        $this->sanitary_registry_id = $id;
        $this->sanitaryRegistrySearch = $number;
        $this->showSanitaryRegistriesDropdown = false;
        $this->resetErrorBag('sanitary_registry_id');
    }

    public function openQuickRegistryModal(): void
    {
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
            'quickRegistryExpirationDate' => ['required', 'date', 'after:today'],
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
            'quickRegistryExpirationDate.after' => 'La fecha de vencimiento debe ser posterior a la fecha actual.',
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

    /**
     * Submit and save medicine.
     */
    public function save(MedicineService $medicineService): void
    {
        // If barcode is empty, generate one before validating
        if (empty($this->barcode)) {
            $this->generateInternalBarcode();
        }

        $validated = $this->validate();

        // Check if combination already exists in the database
        $duplicate = $medicineService->findDuplicate($validated);
        if ($duplicate) {
            $this->duplicateMedicineId = $duplicate->id;
            $this->duplicateMedicineName = $duplicate->name;
            $this->duplicateMedicinePresentation = $duplicate->presentation;
            
            $this->dispatch('open-modal', 'duplicate-medicine-modal');
            return;
        }

        $medicineService->create($validated);

        session()->flash('success', 'El medicamento ha sido registrado con éxito.');
        $this->redirect(route('medicines.index'), navigate: true);
    }

    /**
     * Link duplicate barcode and complete transaction.
     */
    public function linkDuplicateBarcode(MedicineService $medicineService): void
    {
        $medicine = Medicine::findOrFail($this->duplicateMedicineId);

        try {
            $medicineService->linkBarcode($medicine, $this->barcode);
            session()->flash('success', 'El código de barras ha sido vinculado al medicamento existente con éxito.');
            $this->dispatch('close-modal', 'duplicate-medicine-modal');
            $this->redirect(route('medicines.index'), navigate: true);
        } catch (ValidationException $e) {
            $this->addError('barcode', $e->getMessage());
            $this->dispatch('close-modal', 'duplicate-medicine-modal');
        }
    }

    /**
     * Provide options and lists to UI.
     */
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

        // Load all laboratories for the quick registry modal dropdown select
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
        <h1 class="text-2xl font-bold text-blue-900 dark:text-white">Nuevo Medicamento</h1>
        <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
            Registra un nuevo medicamento con su información técnica, código de barras y parámetros logísticos.
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

    <!-- Card Form -->
    <div class="bg-white border border-slate-100 shadow-sm rounded-2xl p-6 md:p-8">
        <form wire:submit="save" class="space-y-6">
            
            <!-- Section: Identificación Básica -->
            <div class="border-b border-slate-100 pb-4">
                <h3 class="text-md font-bold text-blue-900 mb-4">Identificación del Producto</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Barcode with Auto-Generate -->
                    <div>
                        <x-input-label for="barcode" value="Código de Barras" class="text-blue-900 font-semibold mb-1" />
                        <div class="flex gap-2">
                            <x-text-input wire:model="barcode" id="barcode" type="text" class="block w-full mt-1" placeholder="Ingrese o escanee el código" autofocus />
                            <button type="button" wire:click="generateInternalBarcode" 
                                class="mt-1 px-3 py-2 bg-slate-100 hover:bg-slate-200 border border-slate-300 rounded-md text-sm font-semibold text-slate-700 transition cursor-pointer shrink-0"
                                title="Generar código interno único">
                                Auto
                            </button>
                        </div>
                        <x-input-error :messages="$errors->get('barcode')" class="mt-2" />
                        <p class="text-xs text-slate-400 mt-1">Manual: de 8 a 14 dígitos numéricos.</p>
                    </div>

                    <!-- Commercial Name -->
                    <div>
                        <x-input-label for="name" value="Nombre Comercial" class="text-blue-900 font-semibold mb-1" />
                        <x-text-input wire:model="name" id="name" type="text" class="block w-full mt-1" placeholder="Ej. Acetaminofén MK" required />
                        <x-input-error :messages="$errors->get('name')" class="mt-2" />
                    </div>

                    <!-- Generic Name -->
                    <div>
                        <x-input-label for="generic_name" value="Nombre Genérico / Principio Activo" class="text-blue-900 font-semibold mb-1" />
                        <x-text-input wire:model="generic_name" id="generic_name" type="text" class="block w-full mt-1" placeholder="Ej. Acetaminofén" />
                        <x-input-error :messages="$errors->get('generic_name')" class="mt-2" />
                    </div>

                    <!-- Category -->
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

            <!-- Section: Especificaciones Técnicas y Presentación -->
            <div class="border-b border-slate-100 pb-4">
                <h3 class="text-md font-bold text-blue-900 mb-4">Potencia y Presentación</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Concentration Value -->
                    <div>
                        <x-input-label for="concentration_value" value="Valor de Concentración" class="text-blue-900 font-semibold mb-1" />
                        <x-text-input wire:model="concentration_value" id="concentration_value" type="number" step="0.01" class="block w-full mt-1" placeholder="Ej. 500" required />
                        <x-input-error :messages="$errors->get('concentration_value')" class="mt-2" />
                    </div>

                    <!-- Concentration Unit -->
                    <div>
                        <x-input-label for="concentration_unit_id" value="Unidad de Concentración" class="text-blue-900 font-semibold mb-1" />
                        <select wire:model="concentration_unit_id" id="concentration_unit_id" 
                            class="border-slate-100 bg-slate-50 text-blue-900 focus:border-lime-500 focus:ring-lime-500 rounded-md shadow-sm block w-full mt-1 p-3 text-sm cursor-pointer" required>
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
                            class="border-slate-100 bg-slate-50 text-blue-900 focus:border-lime-500 focus:ring-lime-500 rounded-md shadow-sm block w-full mt-1 p-3 text-sm cursor-pointer" required>
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
                        <x-text-input wire:model="content_quantity" id="content_quantity" type="number" class="block w-full mt-1" placeholder="Ej. 30" required />
                        <x-input-error :messages="$errors->get('content_quantity')" class="mt-2" />
                    </div>

                    <!-- Content Unit -->
                    <div>
                        <x-input-label for="content_unit_id" value="Unidad de Contenido" class="text-blue-900 font-semibold mb-1" />
                        <select wire:model="content_unit_id" id="content_unit_id" 
                            class="border-slate-100 bg-slate-50 text-blue-900 focus:border-lime-500 focus:ring-lime-500 rounded-md shadow-sm block w-full mt-1 p-3 text-sm cursor-pointer" required>
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
                            <x-text-input wire:model.live="laboratorySearch" id="laboratorySearch" type="text" class="block w-full mt-1" 
                                placeholder="Escriba para buscar laboratorio..." 
                                @focus="labDropdown = true" required />
                            
                            @if($laboratory_id)
                                <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-lime-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </span>
                            @endif
                        </div>

                        <!-- Dropdown List -->
                        @if($showLaboratoriesDropdown)
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

                    <!-- Searchable Sanitary Registry Selection (Only Valid/Vigente) -->
                    <div class="relative" @click.away="regDropdown = false">
                        <x-input-label for="sanitaryRegistrySearch" value="Registro Sanitario (INVIMA)" class="text-blue-900 font-semibold mb-1" />
                        <div class="relative">
                            <x-text-input wire:model.live="sanitaryRegistrySearch" id="sanitaryRegistrySearch" type="text" class="block w-full mt-1" 
                                placeholder="Escriba para buscar registro INVIMA..." 
                                @focus="regDropdown = true" required />
                            
                            @if($sanitary_registry_id)
                                <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-lime-600">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </span>
                            @endif
                        </div>

                        <!-- Dropdown List -->
                        @if($showSanitaryRegistriesDropdown)
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
                <x-primary-button class="bg-blue-900 hover:bg-lime-500 hover:text-blue-950 text-white font-medium px-6 py-2.5 rounded-lg shadow-sm transition duration-150 ease-in-out cursor-pointer">
                    {{ __('Registrar Medicamento') }}
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
            <p class="mt-2 text-sm text-slate-600 font-normal">
                Registra rápidamente el nuevo laboratorio fabricante. Al guardar, se seleccionará automáticamente.
            </p>

            <form wire:submit.prevent="saveQuickLaboratory" class="mt-4 space-y-4">
                <div>
                    <x-input-label for="quickLabName" value="Nombre del Laboratorio" class="text-blue-900 font-semibold mb-1" />
                    <x-text-input wire:model="quickLabName" id="quickLabName" type="text" class="block w-full mt-1" required />
                    <x-input-error :messages="$errors->get('quickLabName')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="quickLabDescription" value="Descripción (Opcional)" class="text-blue-900 font-semibold mb-1" />
                    <textarea wire:model="quickLabDescription" id="quickLabDescription" rows="3" 
                        class="border-slate-100 bg-slate-50 text-blue-900 focus:border-lime-500 focus:ring-lime-500 rounded-md shadow-sm block w-full mt-1 p-3 text-sm" 
                        placeholder="Descripción opcional..."></textarea>
                    <x-input-error :messages="$errors->get('quickLabDescription')" class="mt-1" />
                </div>

                <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4">
                    <x-secondary-button x-on:click="$dispatch('close')" class="cursor-pointer">
                        Cancelar
                    </x-secondary-button>
                    <x-primary-button type="submit" class="bg-blue-900 hover:bg-lime-500 hover:text-blue-950 text-white font-medium px-6 py-2.5 rounded-lg shadow-sm transition cursor-pointer">
                        Registrar y Seleccionar
                    </x-primary-button>
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
            <p class="mt-2 text-sm text-slate-600 font-normal">
                Registra un certificado sanitario en renovación o vigente. Al guardar, se seleccionará automáticamente.
            </p>

            <form wire:submit.prevent="saveQuickSanitaryRegistry" class="mt-4 space-y-4">
                <!-- Registration Number -->
                <div>
                    <x-input-label for="quickRegistryNumber" value="Número de Registro Sanitario" class="text-blue-900 font-semibold mb-1" />
                    <x-text-input wire:model="quickRegistryNumber" id="quickRegistryNumber" type="text" class="block w-full mt-1 uppercase" placeholder="Ej. INVIMA 2026M-1234567" required />
                    <x-input-error :messages="$errors->get('quickRegistryNumber')" class="mt-1" />
                </div>

                <!-- Laboratory (Select) -->
                <div>
                    <x-input-label for="quickRegistryLabId" value="Laboratorio Fabricante" class="text-blue-900 font-semibold mb-1" />
                    <select wire:model="quickRegistryLabId" id="quickRegistryLabId" 
                        class="border-slate-100 bg-slate-50 text-blue-900 focus:border-lime-500 focus:ring-lime-500 rounded-md shadow-sm block w-full mt-1 p-3 text-sm cursor-pointer" required>
                        <option value="">Seleccione laboratorio...</option>
                        @foreach($allLaboratories as $lab)
                            <option value="{{ $lab->id }}">{{ $lab->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('quickRegistryLabId')" class="mt-1" />
                </div>

                <!-- Expiration Date -->
                <div>
                    <x-input-label for="quickRegistryExpirationDate" value="Fecha de Vencimiento" class="text-blue-900 font-semibold mb-1" />
                    <x-text-input wire:model="quickRegistryExpirationDate" id="quickRegistryExpirationDate" type="date" class="block w-full mt-1" required />
                    <x-input-error :messages="$errors->get('quickRegistryExpirationDate')" class="mt-1" />
                </div>

                <!-- Status -->
                <div>
                    <x-input-label for="quickRegistryStatus" value="Estado" class="text-blue-900 font-semibold mb-1" />
                    <select wire:model="quickRegistryStatus" id="quickRegistryStatus" 
                        class="border-slate-100 bg-slate-50 text-blue-900 focus:border-lime-500 focus:ring-lime-500 rounded-md shadow-sm block w-full mt-1 p-3 text-sm cursor-pointer" required>
                        <option value="valid">Vigente</option>
                        <option value="expired">Vencido</option>
                        <option value="under_renewal">En renovación</option>
                    </select>
                    <x-input-error :messages="$errors->get('quickRegistryStatus')" class="mt-1" />
                </div>

                <!-- Description -->
                <div>
                    <x-input-label for="quickRegistryDescription" value="Descripción (Opcional)" class="text-blue-900 font-semibold mb-1" />
                    <textarea wire:model="quickRegistryDescription" id="quickRegistryDescription" rows="2" 
                        class="border-slate-100 bg-slate-50 text-blue-900 focus:border-lime-500 focus:ring-lime-500 rounded-md shadow-sm block w-full mt-1 p-3 text-sm" 
                        placeholder="Notas adicionales..."></textarea>
                    <x-input-error :messages="$errors->get('quickRegistryDescription')" class="mt-1" />
                </div>

                <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4">
                    <x-secondary-button x-on:click="$dispatch('close')" class="cursor-pointer">
                        Cancelar
                    </x-secondary-button>
                    <x-primary-button type="submit" class="bg-blue-900 hover:bg-lime-500 hover:text-blue-950 text-white font-medium px-6 py-2.5 rounded-lg shadow-sm transition cursor-pointer">
                        Registrar y Seleccionar
                    </x-primary-button>
                </div>
            </form>
        </div>
    </x-modal>

    <!-- Duplicate Medicine Confirmation Modal -->
    <x-modal name="duplicate-medicine-modal" focusable>
        <div class="p-6">
            <h2 class="text-lg font-bold text-blue-900">
                Medicamento Duplicado Detectado
            </h2>
            <p class="mt-3 text-sm text-slate-700">
                Ya existe un medicamento registrado en el catálogo con los mismos datos técnicos:
            </p>
            
            <div class="mt-3 p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-1.5 text-sm font-semibold text-blue-950">
                <div><span class="text-slate-500 font-normal">Nombre Comercial:</span> {{ $duplicateMedicineName }}</div>
                <div><span class="text-slate-500 font-normal">Presentación:</span> {{ $duplicateMedicinePresentation }}</div>
            </div>

            <p class="mt-4 text-sm text-slate-600">
                ¿Desea vincular el nuevo código de barras (<strong class="text-blue-900 font-bold">{{ $barcode }}</strong>) a este medicamento existente, o cancelar para corregir la información?
            </p>

            <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4">
                <x-secondary-button x-on:click="$dispatch('close')" class="cursor-pointer">
                    Cancelar
                </x-secondary-button>
                <x-primary-button wire:click="linkDuplicateBarcode" class="bg-blue-900 hover:bg-lime-500 hover:text-blue-950 text-white font-medium px-6 py-2.5 rounded-lg shadow-sm transition cursor-pointer">
                    Vincular Código
                </x-primary-button>
            </div>
        </div>
    </x-modal>
</div>

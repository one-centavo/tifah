<?php

declare(strict_types=1);

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Laboratory;
use App\Models\SanitaryRegistry;
use App\Services\LaboratoryService;
use App\Services\SanitaryRegistryService;
use App\Http\Requests\SanitaryRegistry\UpdateSanitaryRegistryRequest;

new #[Layout('layouts.app')] class extends Component {
    public SanitaryRegistry $sanitary_registry;

    public string $registration_number = '';
    public ?int $laboratory_id = null;
    public string $expiration_date = '';
    public string $status = 'valid';
    public string $description = '';

    // Search and select laboratory state
    public string $laboratorySearch = '';
    public bool $showLaboratoriesDropdown = false;

    // Quick laboratory creation state
    public string $quickLabName = '';
    public string $quickLabDescription = '';

    /**
     * Initialize the component with model data.
     */
    public function mount(SanitaryRegistry $sanitary_registry): void
    {
        $this->sanitary_registry = $sanitary_registry;
        $this->registration_number = $sanitary_registry->registration_number;
        $this->laboratory_id = $sanitary_registry->laboratory_id;
        $this->expiration_date = $sanitary_registry->expiration_date;
        $this->status = $sanitary_registry->status;
        $this->description = $sanitary_registry->description ?? '';
        $this->laboratorySearch = $sanitary_registry->laboratory->name ?? '';
    }

    /**
     * Get the validation rules.
     */
    protected function rules(): array
    {
        return (new UpdateSanitaryRegistryRequest())->rules($this->sanitary_registry->id);
    }

    /**
     * Get the custom validation error messages.
     */
    protected function messages(): array
    {
        return (new UpdateSanitaryRegistryRequest())->messages();
    }

    /**
     * Handle input change for laboratory search.
     */
    public function updatedLaboratorySearch(): void
    {
        $this->laboratory_id = null;
        $this->showLaboratoriesDropdown = true;
    }

    /**
     * Select a laboratory from the search results.
     */
    public function selectLaboratory(int $id, string $name): void
    {
        $this->laboratory_id = $id;
        $this->laboratorySearch = $name;
        $this->showLaboratoriesDropdown = false;
        $this->resetErrorBag('laboratory_id');
    }

    /**
     * Open quick laboratory creation modal.
     */
    public function openQuickLabModal(): void
    {
        $this->quickLabName = trim($this->laboratorySearch);
        $this->quickLabDescription = '';
        $this->resetErrorBag(['quickLabName', 'quickLabDescription']);
        $this->showLaboratoriesDropdown = false;
        $this->dispatch('open-modal', 'quick-laboratory-modal');
    }

    /**
     * Save the quick laboratory and select it.
     */
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
     * Update the sanitary registry details and redirect to index.
     */
    public function save(SanitaryRegistryService $service): void
    {
        $validated = $this->validate();

        $service->update($this->sanitary_registry, $validated);

        session()->flash('success', 'El registro sanitario ha sido actualizado con éxito.');

        $this->redirect(route('sanitary-registries.index'), navigate: true);
    }

    /**
     * Provide list of filtered laboratories.
     */
    public function with(): array
    {
        $laboratories = [];
        if ($this->showLaboratoriesDropdown) {
            $laboratories = Laboratory::query()
                ->where('name', 'like', '%' . trim($this->laboratorySearch) . '%')
                ->orderBy('name')
                ->take(10)
                ->get();
        }

        return [
            'filteredLaboratories' => $laboratories,
        ];
    }
}; ?>

<div class="max-w-2xl mx-auto py-8 px-4 sm:px-6 lg:px-8" x-data="{ dropdownOpen: @entangle('showLaboratoriesDropdown') }">
    <!-- Header/Back Navigation -->
    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('sanitary-registries.index') }}" wire:navigate
            class="inline-flex items-center text-sm font-medium text-slate-600 hover:text-blue-900 transition-colors gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"></path>
            </svg>
            <span>Volver a Registros Sanitarios</span>
        </a>
    </div>

    <!-- Title -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-blue-900 dark:text-white">Editar Registro Sanitario (INVIMA)</h1>
        <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
            Modifica la información del registro sanitario previamente registrado en el sistema.
        </p>
    </div>

    <!-- Quick Lab Alert Success -->
    @if (session()->has('success_quick_lab'))
        <div class="mb-6 p-4 bg-lime-50 border border-lime-200 text-lime-800 rounded-xl flex items-center shadow-sm animate-pulse" role="alert">
            <svg class="w-5 h-5 mr-2 text-lime-600 shrink-0" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            <span class="font-medium">{{ session('success_quick_lab') }}</span>
        </div>
    @endif

    <!-- Card Form -->
    <div class="bg-white border border-slate-100 shadow-sm rounded-2xl p-6 md:p-8">
        <form wire:submit="save" class="space-y-6">
            <!-- Registration Number -->
            <div>
                <x-input-label for="registration_number" value="Número de Registro Sanitario" class="text-blue-900 font-semibold mb-1" />
                <x-text-input wire:model="registration_number" id="registration_number" type="text" class="block w-full mt-1 uppercase" placeholder="Ej. INVIMA 2026M-1234567" required autofocus />
                <x-input-error :messages="$errors->get('registration_number')" class="mt-2" />
                <p class="text-xs text-slate-400 mt-1">Debe seguir el formato oficial, por ejemplo: INVIMA 2026M-1234567.</p>
            </div>

            <!-- Searchable Laboratory Selection -->
            <div class="relative" @click.away="dropdownOpen = false">
                <x-input-label for="laboratorySearch" value="Laboratorio Fabricante" class="text-blue-900 font-semibold mb-1" />
                <div class="relative">
                    <x-text-input wire:model.live="laboratorySearch" id="laboratorySearch" type="text" class="block w-full mt-1" 
                        placeholder="Escriba para buscar laboratorio..." 
                        @focus="dropdownOpen = true" required />
                    
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
                
                <input type="hidden" wire:model="laboratory_id" name="laboratory_id">
                <x-input-error :messages="$errors->get('laboratory_id')" class="mt-2" />
            </div>

            <!-- Expiration Date -->
            <div>
                <x-input-label for="expiration_date" value="Fecha de Vencimiento" class="text-blue-900 font-semibold mb-1" />
                <x-text-input wire:model="expiration_date" id="expiration_date" type="date" class="block w-full mt-1" required />
                <x-input-error :messages="$errors->get('expiration_date')" class="mt-2" />
            </div>

            <!-- Status -->
            <div>
                <x-input-label for="status" value="Estado" class="text-blue-900 font-semibold mb-1" />
                <select wire:model="status" id="status" 
                    class="border-slate-100 bg-slate-50 text-blue-900 focus:border-lime-500 focus:ring-lime-500 rounded-md shadow-sm block w-full mt-1 p-3 text-sm cursor-pointer">
                    <option value="valid">Vigente</option>
                    <option value="expired">Vencido</option>
                    <option value="under_renewal">En renovación</option>
                </select>
                <x-input-error :messages="$errors->get('status')" class="mt-2" />
            </div>

            <!-- Description -->
            <div>
                <x-input-label for="description" value="Descripción / Notas Internas (Opcional)" class="text-blue-900 font-semibold mb-1" />
                <textarea wire:model="description" id="description" rows="4" 
                    class="border-slate-100 bg-slate-50 text-blue-900 focus:border-lime-500 focus:ring-lime-500 rounded-md shadow-sm block w-full mt-1 p-3 text-sm" 
                    placeholder="Agregue detalles o restricciones adicionales sobre este registro sanitario..."></textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>

            <!-- Submit buttons -->
            <div class="flex items-center justify-end space-x-4 pt-4 border-t border-slate-100">
                <a href="{{ route('sanitary-registries.index') }}" wire:navigate class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-800 transition-colors">
                    Cancelar
                </a>
                <x-primary-button class="bg-blue-900 hover:bg-lime-500 hover:text-blue-950 text-white font-medium px-6 py-2.5 rounded-lg shadow-sm transition duration-150 ease-in-out cursor-pointer">
                    {{ __('Guardar Cambios') }}
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
                Registra rápidamente el nuevo laboratorio fabricante. Una vez guardado, se seleccionará automáticamente.
            </p>

            <form wire:submit.prevent="saveQuickLaboratory" class="mt-4 space-y-4">
                <!-- Name -->
                <div>
                    <x-input-label for="quickLabName" value="Nombre del Laboratorio" class="text-blue-900 font-semibold mb-1" />
                    <x-text-input wire:model="quickLabName" id="quickLabName" type="text" class="block w-full mt-1" required />
                    <x-input-error :messages="$errors->get('quickLabName')" class="mt-1" />
                </div>

                <!-- Description -->
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
</div>

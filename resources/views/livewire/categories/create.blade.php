<?php

declare(strict_types=1);

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Services\CategoryService;
use App\Http\Requests\Category\StoreCategoryRequest;

new #[Layout('layouts.app')] class extends Component {
    public string $name = '';
    public string $description = '';
    public bool $is_cold_chain = false;
    public bool $is_special_control = false;

    /**
     * Get the validation rules.
     *
     * @return array<string, array<int, string>>
     */
    protected function rules(): array
    {
        return (new StoreCategoryRequest())->rules();
    }

    /**
     * Get the custom validation error messages.
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return (new StoreCategoryRequest())->messages();
    }

    /**
     * Create the category and reset the form.
     */
    public function save(CategoryService $categoryService): void
    {
        $validated = $this->validate();

        $categoryService->create($validated);

        session()->flash('success', 'La categoría ha sido registrada con éxito.');

        $this->reset(['name', 'description', 'is_cold_chain', 'is_special_control']);
    }
}; ?>

<div class="max-w-2xl mx-auto py-8">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-blue-900 dark:text-white">Nueva Categoría</h1>
        <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
            Registra una nueva categoría de productos para configurar sus parámetros logísticos y de almacenamiento por defecto.
        </p>
    </div>

    <!-- Alert Success -->
    @if (session()->has('success'))
        <div class="mb-6 p-4 bg-lime-50 border border-lime-200 text-lime-800 rounded-xl flex items-center shadow-sm" role="alert" id="success-alert">
            <svg class="w-5 h-5 mr-2 text-lime-600 shrink-0" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    <!-- Card Form -->
    <div class="bg-white border border-slate-100 shadow-sm rounded-2xl p-6 md:p-8">
        <form wire:submit="save" class="space-y-6">
            <!-- Name -->
            <div>
                <x-input-label for="name" value="Nombre de la Categoría" class="text-blue-900 font-semibold mb-1" />
                <x-text-input wire:model="name" id="name" type="text" class="block w-full mt-1" placeholder="Ej. Antibióticos, Analgésicos" required autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <!-- Description -->
            <div>
                <x-input-label for="description" value="Descripción (Opcional)" class="text-blue-900 font-semibold mb-1" />
                <textarea wire:model="description" id="description" rows="4" 
                    class="border-slate-100 bg-slate-50 text-blue-900 focus:border-lime-500 focus:ring-lime-500 rounded-md shadow-sm block w-full mt-1 p-3 text-sm" 
                    placeholder="Describe el propósito o tipo de productos incluidos en esta categoría..."></textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>

            <!-- Logistical flags -->
            <div class="bg-slate-50 border border-slate-100/50 rounded-xl p-4 md:p-6 space-y-4">
                <h3 class="text-sm font-semibold text-blue-900 mb-2">Restricciones Logísticas</h3>
                
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input wire:model="is_cold_chain" id="is_cold_chain" type="checkbox" 
                            class="w-4 h-4 rounded bg-slate-100 border-slate-200 text-blue-900 shadow-sm focus:ring-lime-500">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="is_cold_chain" class="font-medium text-blue-900">Requiere cadena de frío</label>
                        <p class="text-xs text-slate-600">Activa esta opción si los productos de esta categoría requieren almacenamiento refrigerado (2°C - 8°C).</p>
                    </div>
                </div>

                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input wire:model="is_special_control" id="is_special_control" type="checkbox" 
                            class="w-4 h-4 rounded bg-slate-100 border-slate-200 text-blue-900 shadow-sm focus:ring-lime-500">
                    </div>
                    <div class="ml-3 text-sm">
                        <label for="is_special_control" class="font-medium text-blue-900">Sustancia de control especial</label>
                        <p class="text-xs text-slate-600">Activa esta opción si los productos están regulados por el Fondo Nacional de Estupefacientes u otros entes de control.</p>
                    </div>
                </div>
            </div>

            <!-- Submit buttons -->
            <div class="flex items-center justify-end space-x-4 pt-4 border-t border-slate-100">
                <x-primary-button class="bg-blue-900 hover:bg-lime-500 text-white font-medium px-6 py-2.5 rounded-lg shadow-sm transition duration-150 ease-in-out cursor-pointer">
                    {{ __('Registrar Categoría') }}
                </x-primary-button>
            </div>
        </form>
    </div>
</div>

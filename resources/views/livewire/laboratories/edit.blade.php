<?php

declare(strict_types=1);

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Laboratory;
use App\Services\LaboratoryService;
use App\Http\Requests\Laboratory\UpdateLaboratoryRequest;

new #[Layout('layouts.app')] class extends Component {
    public Laboratory $laboratory;

    public string $name = '';
    public string $description = '';

    /**
     * Initialize the component with laboratory data.
     */
    public function mount(Laboratory $laboratory): void
    {
        $this->laboratory = $laboratory;
        $this->name = $laboratory->name;
        $this->description = $laboratory->description ?? '';
    }

    /**
     * Get the validation rules.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return (new UpdateLaboratoryRequest())->rules($this->laboratory->id);
    }

    /**
     * Get the custom validation error messages.
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return (new UpdateLaboratoryRequest())->messages();
    }

    /**
     * Update the laboratory details and redirect to index.
     */
    public function save(LaboratoryService $laboratoryService): void
    {
        $validated = $this->validate();

        $laboratoryService->update($this->laboratory, $validated);

        session()->flash('success', 'El laboratorio ha sido actualizado con éxito.');

        $this->redirect(route('laboratories.index'), navigate: true);
    }
}; ?>

<div class="max-w-2xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <!-- Header/Back Navigation -->
    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('laboratories.index') }}" wire:navigate
            class="inline-flex items-center text-sm font-medium text-slate-600 hover:text-blue-900 transition-colors gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"></path>
            </svg>
            <span>Volver a Laboratorios</span>
        </a>
    </div>

    <!-- Title -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-blue-900 dark:text-white">Editar Laboratorio</h1>
        <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
            Modifica la información del laboratorio previamente registrado en el sistema.
        </p>
    </div>

    <!-- Card Form -->
    <div class="bg-white border border-slate-100 shadow-sm rounded-2xl p-6 md:p-8">
        <form wire:submit="save" class="space-y-6">
            <!-- Name -->
            <div>
                <x-input-label for="name" value="Nombre del Laboratorio" class="text-blue-900 font-semibold mb-1" />
                <x-text-input wire:model="name" id="name" type="text" class="block w-full mt-1" placeholder="Ej. Pfizer, Genfar, Tecnoquímicas" required autofocus />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <!-- Description -->
            <div>
                <x-input-label for="description" value="Descripción (Opcional)" class="text-blue-900 font-semibold mb-1" />
                <textarea wire:model="description" id="description" rows="4" 
                    class="border-slate-100 bg-slate-50 text-blue-900 focus:border-lime-500 focus:ring-lime-500 rounded-md shadow-sm block w-full mt-1 p-3 text-sm" 
                    placeholder="Describe notas internas o información sobre el laboratorio fabricante..."></textarea>
                <x-input-error :messages="$errors->get('description')" class="mt-2" />
            </div>

            <!-- Submit buttons -->
            <div class="flex items-center justify-end space-x-4 pt-4 border-t border-slate-100">
                <a href="{{ route('laboratories.index') }}" wire:navigate class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-800 transition-colors">
                    Cancelar
                </a>
                <x-primary-button class="bg-blue-900 hover:bg-lime-500 hover:text-blue-950 text-white font-medium px-6 py-2.5 rounded-lg shadow-sm transition duration-150 ease-in-out cursor-pointer">
                    {{ __('Guardar Cambios') }}
                </x-primary-button>
            </div>
        </form>
    </div>
</div>

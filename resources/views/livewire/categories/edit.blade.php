<?php

declare(strict_types=1);

use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use App\Models\Category;
use App\Services\CategoryService;
use App\Http\Requests\Category\UpdateCategoryRequest;

new #[Layout('layouts.app')] class extends Component {
    public Category $category;

    public string $name = '';
    public string $description = '';
    public bool $is_cold_chain = false;
    public bool $is_special_control = false;

    /**
     * Initialize the component with category data.
     */
    public function mount(Category $category): void
    {
        $this->category = $category;
        $this->name = $category->name;
        $this->description = $category->description ?? '';
        $this->is_cold_chain = (bool) $category->is_cold_chain;
        $this->is_special_control = (bool) $category->is_special_control;
    }

    /**
     * Get the validation rules.
     *
     * @return array<string, array<int, mixed>>
     */
    protected function rules(): array
    {
        return (new UpdateCategoryRequest())->rules($this->category->id);
    }

    /**
     * Get the custom validation error messages.
     *
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return (new UpdateCategoryRequest())->messages();
    }

    /**
     * Update the category details.
     */
    public function save(CategoryService $categoryService): void
    {
        $validated = $this->validate();

        $categoryService->update($this->category, $validated);

        session()->flash('success', 'La categoría ha sido actualizada con éxito.');
    }
}; ?>

<div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <!-- Header/Back Navigation -->
    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('categories.index') }}" wire:navigate
            class="inline-flex items-center text-sm font-medium text-slate-600 hover:text-blue-900 transition-colors gap-1.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"></path>
            </svg>
            <span>Volver a Categorías</span>
        </a>
    </div>

    <!-- Title -->
    <div class="mb-8">
        <h1 class="text-3xl font-extrabold text-blue-900 dark:text-white tracking-tight">Editar Categoría</h1>
        <p class="text-sm text-slate-600 dark:text-slate-400 mt-2">
            Modifica la configuración logística y sanitaria de la categoría. Estos cambios afectarán a todos los medicamentos asociados.
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

    <div class="space-y-8">
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
                        {{ __('Guardar Cambios') }}
                    </x-primary-button>
                </div>
            </form>
        </div>

        <!-- Affected Medicines Section -->
        <div class="bg-white border border-slate-100 shadow-sm rounded-2xl p-6 md:p-8">
            <h2 class="text-xl font-bold text-blue-900 mb-2">Medicamentos Afectados</h2>
            <p class="text-sm text-slate-600 mb-6">
                Los siguientes medicamentos pertenecen a esta categoría y heredan o se ven afectados por los cambios de clasificación y controles de cadena de frío o regulaciones sanitarias.
            </p>

            @if($category->medicines->isEmpty())
                <div class="p-6 text-center bg-slate-50 rounded-xl border border-slate-100">
                    <p class="text-sm text-slate-500">No hay medicamentos registrados en esta categoría actualmente.</p>
                </div>
            @else
                <div class="overflow-x-auto border border-slate-100 rounded-xl">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50">
                            <tr>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-blue-900 uppercase tracking-wider">Medicamento</th>
                                <th scope="col" class="px-6 py-3.5 text-left text-xs font-bold text-blue-900 uppercase tracking-wider">Nombre Genérico</th>
                                <th scope="col" class="px-6 py-3.5 text-center text-xs font-bold text-blue-900 uppercase tracking-wider">Cadena de Frío</th>
                                <th scope="col" class="px-6 py-3.5 text-center text-xs font-bold text-blue-900 uppercase tracking-wider">Control Especial</th>
                                <th scope="col" class="px-6 py-3.5 text-right text-xs font-bold text-blue-900 uppercase tracking-wider">Precio Venta</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @foreach($category->medicines as $medicine)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-blue-900">{{ $medicine->name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-slate-600">{{ $medicine->generic_name }}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        @if($medicine->is_cold_chain)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                                Requerido
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-50 text-slate-500">
                                                No
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        @if($medicine->is_special_control)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-50 text-purple-700">
                                                Controlado
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-slate-50 text-slate-500">
                                                No
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-blue-900">
                                        ${{ number_format((float) $medicine->selling_price, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

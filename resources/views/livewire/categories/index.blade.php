<?php

declare(strict_types=1);

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\Category;
use App\Services\CategoryService;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $coldChain = 'all';
    public string $specialControl = 'all';
    public string $status = 'active';
    public string $sortField = 'name';
    public string $sortDirection = 'asc';

    /**
     * Reset pagination page when filters are updated.
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingColdChain(): void
    {
        $this->resetPage();
    }

    public function updatingSpecialControl(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    /**
     * Handle column sorting.
     */
    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public ?int $categoryIdBeingDeleted = null;
    public string $categoryNameBeingDeleted = '';

    /**
     * Confirm the category deletion.
     */
    public function confirmCategoryDeletion(int $id): void
    {
        $category = Category::findOrFail($id);
        $this->categoryIdBeingDeleted = $category->id;
        $this->categoryNameBeingDeleted = $category->name;
        $this->resetErrorBag();
        $this->dispatch('open-modal', 'confirm-category-deletion');
    }

    /**
     * Delete/Archive a category.
     */
    public function deleteCategory(CategoryService $categoryService): void
    {
        if (!$this->categoryIdBeingDeleted) {
            return;
        }

        $category = Category::findOrFail($this->categoryIdBeingDeleted);

        try {
            $categoryService->delete($category);

            $this->dispatch('close-modal', 'confirm-category-deletion');
            $this->reset(['categoryIdBeingDeleted', 'categoryNameBeingDeleted']);

            session()->flash('success', 'La categoría ha sido eliminada con éxito.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            foreach ($e->errors() as $key => $messages) {
                foreach ($messages as $message) {
                    $this->addError('deletion_error', $message);
                }
            }
        }
    }

    /**
     * Restore a soft-deleted/archived category.
     */
    public function restoreCategory(int $id, CategoryService $categoryService): void
    {
        $category = Category::onlyTrashed()->findOrFail($id);
        $categoryService->restore($category);

        session()->flash('success', 'La categoría ha sido restaurada con éxito.');
    }

    /**
     * Get the categories with pagination.
     */
    public function with(): array
    {
        $query = Category::query();

        // 1. Status Filter (Soft Delete)
        if ($this->status === 'active') {
            // Default, loaded automatically by Eloquent SoftDeletes
        } elseif ($this->status === 'archived') {
            $query->onlyTrashed();
        } elseif ($this->status === 'all') {
            $query->withTrashed();
        }

        // 2. Name Search (partial match)
        if (!empty(trim($this->search))) {
            $query->where('name', 'like', '%' . trim($this->search) . '%');
        }

        // 3. Cold Chain Filter
        if ($this->coldChain === 'yes') {
            $query->where('is_cold_chain', true);
        } elseif ($this->coldChain === 'no') {
            $query->where('is_cold_chain', false);
        }

        // 4. Special Control Filter
        if ($this->specialControl === 'yes') {
            $query->where('is_special_control', true);
        } elseif ($this->specialControl === 'no') {
            $query->where('is_special_control', false);
        }

        // 5. Sorting
        if ($this->sortField === 'status') {
            $direction = $this->sortDirection === 'asc' ? 'DESC' : 'ASC';
            $query->orderByRaw("deleted_at IS NULL {$direction}");
            $query->orderBy('name', 'asc');
        } else {
            $query->orderBy('name', $this->sortDirection);
        }

        return [
            'categories' => $query->paginate(10),
        ];
    }
}; ?>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-blue-900 dark:text-white tracking-tight">Gestión de Categorías</h1>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-2">
                Administra las clasificaciones de medicamentos, controla parámetros logísticos y sanitarios de almacenamiento.
            </p>
        </div>
        <div>
            <a href="{{ route('categories.create') }}" wire:navigate
                class="inline-flex items-center bg-blue-900 hover:bg-lime-500 hover:text-blue-950 text-white font-semibold px-5 py-3 rounded-xl shadow-md transition-all duration-200 ease-in-out gap-2 cursor-pointer transform hover:-translate-y-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path>
                </svg>
                <span>Nueva Categoría</span>
            </a>
        </div>
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

    <!-- Filters Section -->
    <div class="bg-white border border-slate-100 shadow-sm rounded-2xl p-6 mb-6">
        <h2 class="text-lg font-bold text-blue-900 mb-4">Filtrar Categorías</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- Search field -->
            <div>
                <label for="search" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Búsqueda</label>
                <div class="relative">
                    <input type="text" id="search" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre..."
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

            <!-- Cold Chain filter -->
            <div>
                <label for="coldChain" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Cadena de Frío</label>
                <select id="coldChain" wire:model.live="coldChain"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all cursor-pointer">
                    <option value="all">Todos</option>
                    <option value="yes">Sí</option>
                    <option value="no">No</option>
                </select>
            </div>

            <!-- Special Control filter -->
            <div>
                <label for="specialControl" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Control Especial</label>
                <select id="specialControl" wire:model.live="specialControl"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all cursor-pointer">
                    <option value="all">Todos</option>
                    <option value="yes">Sí</option>
                    <option value="no">No</option>
                </select>
            </div>

            <!-- Status filter -->
            <div>
                <label for="status" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Estado</label>
                <select id="status" wire:model.live="status"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all cursor-pointer">
                    <option value="active">Activas</option>
                    <option value="archived">Archivadas</option>
                    <option value="all">Todas</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Card Table -->
    <div class="bg-white border border-slate-100 shadow-sm rounded-2xl overflow-hidden">
        @if(Category::withTrashed()->count() === 0)
            <div class="p-12 text-center">
                <div class="inline-flex p-4 bg-slate-50 text-slate-400 rounded-full mb-4">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5M3.75 5.25h16.5m-16.5 13.5h16.5"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-blue-900 mb-1">No hay categorías registradas</h3>
                <p class="text-sm text-slate-500 mb-6">Comienza registrando la primera categoría de medicamentos en el sistema.</p>
                <a href="{{ route('categories.create') }}" wire:navigate
                    class="inline-flex items-center bg-blue-900 hover:bg-lime-500 hover:text-blue-950 text-white font-semibold px-4 py-2.5 rounded-lg transition-colors cursor-pointer">
                    Registrar Categoría
                </a>
            </div>
        @elseif($categories->isEmpty())
            <div class="p-12 text-center">
                <div class="inline-flex p-4 bg-amber-50 text-amber-500 rounded-full mb-4">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-blue-900 mb-1">Sin resultados</h3>
                <p class="text-sm text-slate-500">No se encontraron categorías que coincidan con los filtros aplicados</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase tracking-wider">
                                <button type="button" wire:click="sortBy('name')" class="flex items-center gap-1 hover:text-blue-700 font-bold uppercase tracking-wider focus:outline-none">
                                    <span>Nombre</span>
                                    @if($sortField === 'name')
                                        @if($sortDirection === 'asc')
                                            <svg class="w-3.5 h-3.5 text-blue-900" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5"></path>
                                            </svg>
                                        @else
                                            <svg class="w-3.5 h-3.5 text-blue-900" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                                            </svg>
                                        @endif
                                    @else
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9"></path>
                                        </svg>
                                    @endif
                                </button>
                            </th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase tracking-wider">Descripción</th>
                            <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-blue-900 uppercase tracking-wider">Cadena de Frío</th>
                            <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-blue-900 uppercase tracking-wider">Control Especial</th>
                            <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-blue-900 uppercase tracking-wider">
                                <button type="button" wire:click="sortBy('status')" class="inline-flex items-center gap-1 hover:text-blue-700 font-bold uppercase tracking-wider focus:outline-none">
                                    <span>Estado</span>
                                    @if($sortField === 'status')
                                        @if($sortDirection === 'asc')
                                            <svg class="w-3.5 h-3.5 text-blue-900" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5"></path>
                                            </svg>
                                        @else
                                            <svg class="w-3.5 h-3.5 text-blue-900" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"></path>
                                            </svg>
                                        @endif
                                    @else
                                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9"></path>
                                        </svg>
                                    @endif
                                </button>
                            </th>
                            <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-blue-900 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach($categories as $category)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-blue-900">{{ $category->name }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-slate-600 max-w-xs truncate" title="{{ $category->description }}">
                                        {{ $category->description ?: 'Sin descripción' }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($category->is_cold_chain)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                            <span class="w-1.5 h-1.5 mr-1.5 bg-blue-500 rounded-full"></span>
                                            Requerido
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-50 text-slate-500 border border-slate-100">
                                            No aplica
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($category->is_special_control)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-purple-50 text-purple-700 border border-purple-100">
                                            <span class="w-1.5 h-1.5 mr-1.5 bg-purple-500 rounded-full"></span>
                                            Controlado
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-50 text-slate-500 border border-slate-100">
                                            No aplica
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($category->deleted_at)
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
                                            <span class="w-1.5 h-1.5 mr-1.5 bg-slate-400 rounded-full"></span>
                                            Archivada
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-lime-50 text-lime-700 border border-lime-100">
                                            <span class="w-1.5 h-1.5 mr-1.5 bg-lime-500 rounded-full"></span>
                                            Activa
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                    @if($category->deleted_at)
                                        <button type="button" 
                                            wire:click="restoreCategory({{ $category->id }})"
                                            wire:confirm="¿Está seguro de que desea restaurar la categoría '{{ $category->name }}'?"
                                            class="inline-flex items-center text-lime-600 hover:text-lime-900 transition-colors gap-1 cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"></path>
                                            </svg>
                                            <span>Restaurar</span>
                                        </button>
                                    @else
                                        <a href="{{ route('categories.edit', $category) }}" wire:navigate
                                            class="inline-flex items-center text-blue-600 hover:text-blue-900 transition-colors gap-1">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.83 21.75a.75.75 0 01-.342.214l-4.8 1.2a.75.75 0 01-.928-.927l1.2-4.8a.75.75 0 01.214-.342l14.86-14.86zm0 0L19.5 7.125"></path>
                                            </svg>
                                            <span>Editar</span>
                                        </a>
                                        <button type="button" 
                                            wire:click="confirmCategoryDeletion({{ $category->id }})"
                                            class="inline-flex items-center text-red-600 hover:text-red-900 transition-colors gap-1 cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"></path>
                                            </svg>
                                            <span>Eliminar</span>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100">
                {{ $categories->links() }}
            </div>
        @endif
    </div>

    <!-- Deletion Confirmation Modal -->
    <x-modal name="confirm-category-deletion" focusable>
        <div class="p-6">
            <h2 class="text-lg font-bold text-blue-900">
                ¿Está seguro de que desea eliminar la categoría?
            </h2>

            <p class="mt-2 text-sm text-slate-600 font-normal">
                Esta acción archivará la categoría <span class="font-semibold text-blue-950">"{{ $categoryNameBeingDeleted }}"</span>. Dejará de aparecer en los listados activos y formularios de registro, pero se mantendrá en el historial para fines de auditoría y consulta.
            </p>

            <x-input-error :messages="$errors->get('deletion_error')" class="mt-4" />

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')" class="cursor-pointer">
                    Cancelar
                </x-secondary-button>

                <x-danger-button wire:click="deleteCategory" class="cursor-pointer">
                    Eliminar
                </x-danger-button>
            </div>
        </div>
    </x-modal>
</div>

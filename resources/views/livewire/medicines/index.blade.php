<?php

declare(strict_types=1);

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\Medicine;
use App\Models\Category;
use App\Services\MedicineService;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $softDeleteFilter = 'active';
    public string $categoryFilter = 'all';
    public string $sortField = 'name';
    public string $sortDirection = 'asc';

    public ?int $medicineIdBeingDeleted = null;
    public string $medicineNameBeingDeleted = '';

    /**
     * Reset pagination page when filters are updated.
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSoftDeleteFilter(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
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

    /**
     * Confirm the medicine deletion.
     */
    public function confirmMedicineDeletion(int $id): void
    {
        $medicine = Medicine::findOrFail($id);
        $this->medicineIdBeingDeleted = $medicine->id;
        $this->medicineNameBeingDeleted = $medicine->name;
        $this->resetErrorBag();
        $this->dispatch('open-modal', 'confirm-medicine-deletion');
    }

    /**
     * Delete/Archive a medicine.
     */
    public function deleteMedicine(MedicineService $service): void
    {
        if (!$this->medicineIdBeingDeleted) {
            return;
        }

        $medicine = Medicine::findOrFail($this->medicineIdBeingDeleted);

        try {
            $service->delete($medicine);

            $this->dispatch('close-modal', 'confirm-medicine-deletion');
            $this->reset(['medicineIdBeingDeleted', 'medicineNameBeingDeleted']);

            session()->flash('success', 'El medicamento ha sido eliminado con éxito.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            foreach ($e->errors() as $key => $messages) {
                foreach ($messages as $message) {
                    $this->addError('deletion_error', $message);
                }
            }
        }
    }

    /**
     * Get the medicines with pagination and filters.
     */
    public function with(): array
    {
        $query = Medicine::query()
            ->with(['category', 'laboratory', 'sanitaryRegistry', 'container', 'contentUnit', 'barcodes']);

        // 1. Soft Delete Filter
        if ($this->softDeleteFilter === 'active') {
            // Default active, standard behavior
        } elseif ($this->softDeleteFilter === 'archived') {
            $query->onlyTrashed();
        } elseif ($this->softDeleteFilter === 'all') {
            $query->withTrashed();
        }

        // 2. Category Filter
        if ($this->categoryFilter !== 'all') {
            $query->where('category_id', $this->categoryFilter);
        }

        // 3. Search (Name, Generic Name, Barcode)
        if (!empty(trim($this->search))) {
            $searchTerm = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhere('generic_name', 'like', $searchTerm)
                  ->orWhereHas('barcodes', function ($b) use ($searchTerm) {
                      $b->where('barcode', 'like', $searchTerm);
                  });
            });
        }

        // 4. Sorting
        if ($this->sortField === 'category') {
            $query->join('categories', 'medicines.category_id', '=', 'categories.id')
                ->select('medicines.*')
                ->orderBy('categories.name', $this->sortDirection);
        } elseif ($this->sortField === 'laboratory') {
            $query->join('laboratories', 'medicines.laboratory_id', '=', 'laboratories.id')
                ->select('medicines.*')
                ->orderBy('laboratories.name', $this->sortDirection);
        } else {
            $query->orderBy('medicines.' . $this->sortField, $this->sortDirection);
        }

        return [
            'medicines' => $query->paginate(10),
            'categories' => Category::orderBy('name')->get(),
        ];
    }
}; ?>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-blue-900 dark:text-white tracking-tight">Catálogo de Medicamentos</h1>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-2">
                Administra la información técnica de los medicamentos, sus presentaciones y códigos de barras vinculados.
            </p>
        </div>
        <div>
            <a href="{{ route('medicines.create') }}" wire:navigate
                class="inline-flex items-center bg-blue-900 hover:bg-lime-500 hover:text-blue-950 text-white font-semibold px-5 py-3 rounded-xl shadow-md transition-all duration-200 ease-in-out gap-2 cursor-pointer transform hover:-translate-y-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path>
                </svg>
                <span>Nuevo Medicamento</span>
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
        <h2 class="text-lg font-bold text-blue-900 mb-4">Filtrar Medicamentos</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Search field -->
            <div>
                <label for="search" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Búsqueda</label>
                <div class="relative">
                    <input type="text" id="search" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre, principio activo o código..."
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

            <!-- Category Filter -->
            <div>
                <label for="categoryFilter" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Categoría</label>
                <select id="categoryFilter" wire:model.live="categoryFilter"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all cursor-pointer">
                    <option value="all">Todas</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Soft Delete filter -->
            <div>
                <label for="softDeleteFilter" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Visibilidad en Catálogo</label>
                <select id="softDeleteFilter" wire:model.live="softDeleteFilter"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all cursor-pointer">
                    <option value="active">Sí (Activos)</option>
                    <option value="archived">No (Eliminados)</option>
                    <option value="all">Todos</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Card Table -->
    <div class="bg-white border border-slate-100 shadow-sm rounded-2xl overflow-hidden">
        @if(Medicine::withTrashed()->count() === 0)
            <div class="p-12 text-center">
                <div class="inline-flex p-4 bg-slate-50 text-slate-400 rounded-full mb-4">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-blue-900 mb-1">No hay medicamentos registrados</h3>
                <p class="text-sm text-slate-500 mb-6">Comienza registrando el primer medicamento en el catálogo.</p>
                <a href="{{ route('medicines.create') }}" wire:navigate
                    class="inline-flex items-center bg-blue-900 hover:bg-lime-500 hover:text-blue-950 text-white font-semibold px-4 py-2.5 rounded-lg transition-colors cursor-pointer">
                    Registrar Medicamento
                </a>
            </div>
        @elseif($medicines->isEmpty())
            <div class="p-12 text-center">
                <div class="inline-flex p-4 bg-amber-50 text-amber-500 rounded-full mb-4">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-blue-900 mb-1">Sin resultados</h3>
                <p class="text-sm text-slate-500">No se encontraron medicamentos que coincidan con los filtros aplicados</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase tracking-wider">Código(s) de Barras</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase tracking-wider">
                                <button type="button" wire:click="sortBy('name')" class="flex items-center gap-1 hover:text-blue-700 font-bold uppercase tracking-wider focus:outline-none">
                                    <span>Nombre Comercial</span>
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
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase tracking-wider">Nombre Genérico</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase tracking-wider">Concentración</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase tracking-wider">Presentación</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase tracking-wider">
                                <button type="button" wire:click="sortBy('category')" class="flex items-center gap-1 hover:text-blue-700 font-bold uppercase tracking-wider focus:outline-none">
                                    <span>Categoría</span>
                                    @if($sortField === 'category')
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
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase tracking-wider">Laboratorio</th>
                            <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-blue-900 uppercase tracking-wider">Logística</th>
                            <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-blue-900 uppercase tracking-wider">Precio</th>
                            <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-blue-900 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100">
                        @foreach($medicines as $medicine)
                            <tr class="hover:bg-slate-50/50 transition-colors" wire:key="medicine-{{ $medicine->id }}">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">
                                    @foreach($medicine->barcodes as $bar)
                                        <span class="inline-block bg-slate-100 text-slate-800 rounded px-2 py-0.5 text-xs font-mono mb-1 {{ $bar->is_main ? 'border border-blue-200 font-bold' : '' }}" title="{{ $bar->is_main ? 'Código Principal' : 'Código Vinculado' }}">
                                            {{ $bar->barcode }}
                                        </span>
                                    @endforeach
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-blue-950">
                                    {{ $medicine->name }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">
                                    {{ $medicine->generic_name ?: 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                    {{ number_format((float) $medicine->concentration_value, 0) }} {{ $medicine->concentrationUnit->symbol ?? '' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                    {{ $medicine->presentation }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">
                                    {{ $medicine->category->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                    {{ $medicine->laboratory->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm space-y-1">
                                    @if($medicine->is_cold_chain)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-cyan-50 text-cyan-800 border border-cyan-200">
                                            Frío
                                        </span>
                                    @endif
                                    @if($medicine->is_special_control)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-purple-50 text-purple-800 border border-purple-200">
                                            Control Especial
                                        </span>
                                    @endif
                                    @if(!$medicine->is_cold_chain && !$medicine->is_special_control)
                                        <span class="text-slate-400 text-xs">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-semibold text-slate-800">
                                    ${{ number_format((float) $medicine->selling_price, 2) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @if(!$medicine->trashed())
                                        <button type="button" 
                                            wire:click="confirmMedicineDeletion({{ $medicine->id }})"
                                            class="inline-flex items-center text-red-600 hover:text-red-900 transition-colors gap-1 cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"></path>
                                            </svg>
                                            <span>Eliminar</span>
                                        </button>
                                    @else
                                        <span class="text-xs text-slate-400 italic">Eliminado</span>
                                    @endif
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

    <!-- Deletion Confirmation Modal -->
    <x-modal name="confirm-medicine-deletion" focusable>
        <div class="p-6">
            <h2 class="text-lg font-bold text-blue-900">
                ¿Está seguro de que desea eliminar el medicamento?
            </h2>

            <p class="mt-2 text-sm text-slate-600 font-normal">
                Esta acción archivará el medicamento <span class="font-semibold text-blue-950">"{{ $medicineNameBeingDeleted }}"</span>. Se mantendrá en el historial de la base de datos pero se ocultará de las consultas operativas.
            </p>

            <x-input-error :messages="$errors->get('deletion_error')" class="mt-4" />

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')" class="cursor-pointer">
                    Cancelar
                </x-secondary-button>

                <x-danger-button wire:click="deleteMedicine" class="cursor-pointer">
                    Eliminar
                </x-danger-button>
            </div>
        </div>
    </x-modal>
</div>

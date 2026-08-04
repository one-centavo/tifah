<?php

declare(strict_types=1);

use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    public string $search = '';

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

    public ?int $supplierIdBeingDeleted = null;

    public string $supplierNameBeingDeleted = '';

    /**
     * Confirm supplier deletion.
     */
    public function confirmSupplierDeletion(int $id): void
    {
        $supplier = Supplier::findOrFail($id);
        $this->supplierIdBeingDeleted = $supplier->id;
        $this->supplierNameBeingDeleted = $supplier->name;
        $this->resetErrorBag();
        $this->dispatch('open-modal', 'confirm-supplier-deletion');
    }

    /**
     * Soft delete/Archive a supplier.
     */
    public function deleteSupplier(SupplierService $supplierService): void
    {
        if (! $this->supplierIdBeingDeleted) {
            return;
        }

        $supplier = Supplier::findOrFail($this->supplierIdBeingDeleted);

        try {
            $supplierService->delete($supplier);

            $this->dispatch('close-modal', 'confirm-supplier-deletion');
            $this->reset(['supplierIdBeingDeleted', 'supplierNameBeingDeleted']);

            session()->flash('success', 'El proveedor ha sido eliminado con éxito.');
        } catch (ValidationException $e) {
            foreach ($e->errors() as $key => $messages) {
                foreach ($messages as $message) {
                    $this->addError('deletion_error', $message);
                }
            }
        }
    }

    /**
     * Get the suppliers with pagination.
     */
    public function with(): array
    {
        $query = Supplier::query();

        // 1. Status Filter (Soft Delete)
        if ($this->status === 'active') {
            // Default active (will load normal active records)
        } elseif ($this->status === 'archived') {
            $query->onlyTrashed();
        } elseif ($this->status === 'all') {
            $query->withTrashed();
        }

        // 2. Search (partial match Razón Social / NIT)
        if (! empty(trim($this->search))) {
            $term = '%'.trim($this->search).'%';
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', $term)
                    ->orWhere('nit', 'like', $term);
            });
        }

        // 3. Sorting
        if ($this->sortField === 'status') {
            $direction = $this->sortDirection === 'asc' ? 'DESC' : 'ASC';
            $query->orderByRaw("deleted_at IS NULL {$direction}");
            $query->orderBy('name', 'asc');
        } else {
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        return [
            'suppliers' => $query->paginate(10),
        ];
    }
}; ?>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-blue-900 dark:text-white tracking-tight">Gestión de Proveedores</h1>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-2">
                Administra la información de laboratorios y distribuidores mayoristas para formalizar la entrada de mercancía y asegurar la trazabilidad.
            </p>
        </div>
        <div>
            <a href="{{ route('suppliers.create') }}" wire:navigate
                class="inline-flex items-center bg-blue-900 hover:bg-lime-500 hover:text-blue-950 text-white font-semibold px-5 py-3 rounded-xl shadow-md transition-all duration-200 ease-in-out gap-2 cursor-pointer transform hover:-translate-y-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path>
                </svg>
                <span>Nuevo Proveedor</span>
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
        <h2 class="text-lg font-bold text-blue-900 mb-4 font-sans">Filtrar Proveedores</h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <!-- Search field -->
            <div>
                <label for="search" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Búsqueda</label>
                <div class="relative">
                    <input type="text" id="search" wire:model.live.debounce.300ms="search" placeholder="Buscar por Razón Social o NIT..."
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

            <!-- Status filter -->
            <div>
                <label for="status" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Estado</label>
                <select id="status" wire:model.live="status"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all cursor-pointer">
                    <option value="active">Activos</option>
                    <option value="archived">Archivados</option>
                    <option value="all">Todos</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Card Table -->
    <div class="bg-white border border-slate-100 shadow-sm rounded-2xl overflow-hidden">
        @if(Supplier::withTrashed()->count() === 0)
            <div class="p-12 text-center">
                <div class="inline-flex p-4 bg-slate-50 text-slate-400 rounded-full mb-4">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18 9h-3.75A2.25 2.25 0 0012 11.25v2.5"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-blue-900 mb-1">No hay proveedores registrados</h3>
                <p class="text-sm text-slate-500 mb-6">Comienza registrando el primer distribuidor o laboratorio en el sistema.</p>
                <a href="{{ route('suppliers.create') }}" wire:navigate
                    class="inline-flex items-center bg-blue-900 hover:bg-lime-500 hover:text-blue-950 text-white font-semibold px-4 py-2.5 rounded-lg transition-colors cursor-pointer">
                    Registrar Proveedor
                </a>
            </div>
        @elseif($suppliers->isEmpty())
            <div class="p-12 text-center">
                <div class="inline-flex p-4 bg-amber-50 text-amber-500 rounded-full mb-4">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-blue-900 mb-1">Sin resultados</h3>
                <p class="text-sm text-slate-500">No se encontraron proveedores que coincidan con los filtros aplicados</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase tracking-wider">
                                <button type="button" wire:click="sortBy('nit')" class="flex items-center gap-1 hover:text-blue-700 font-bold uppercase tracking-wider focus:outline-none">
                                    <span>NIT</span>
                                    @if($sortField === 'nit')
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
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase tracking-wider">
                                <button type="button" wire:click="sortBy('name')" class="flex items-center gap-1 hover:text-blue-700 font-bold uppercase tracking-wider focus:outline-none">
                                    <span>Razón Social</span>
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
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase tracking-wider">Contacto</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase tracking-wider">Teléfono / Email</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase tracking-wider">Dirección</th>
                            <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-blue-900 uppercase tracking-wider">Estado</th>
                            <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-blue-900 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100">
                        @foreach($suppliers as $supplier)
                            <tr class="hover:bg-slate-50/50 transition-colors" wire:key="supplier-{{ $supplier->id }}">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-700">
                                    {{ $supplier->nit }}-{{ $supplier->dv }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-blue-950">
                                    {{ $supplier->name }}
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600 max-w-xs truncate">
                                    {{ $supplier->contact_person ?: 'Sin contacto' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600">
                                    <div class="font-medium text-slate-700">{{ $supplier->phone_number }}</div>
                                    <div class="text-xs text-slate-400">{{ $supplier->email }}</div>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600 max-w-xs truncate">
                                    {{ $supplier->address }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                    @if($supplier->trashed())
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-800">
                                            Archivado
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-lime-50 text-lime-800 border border-lime-200">
                                            Activo
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                    @if(!$supplier->trashed())
                                        <a href="{{ route('suppliers.edit', $supplier->id) }}" wire:navigate
                                            class="inline-flex items-center text-blue-600 hover:text-blue-900 transition-colors gap-1 cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"></path>
                                            </svg>
                                            <span>Editar</span>
                                        </a>

                                        <button type="button" 
                                            wire:click="confirmSupplierDeletion({{ $supplier->id }})"
                                            class="inline-flex items-center text-red-600 hover:text-red-900 transition-colors gap-1 cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"></path>
                                            </svg>
                                            <span>Eliminar</span>
                                        </button>
                                    @else
                                        <span class="text-xs text-slate-400">Sin acciones</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100">
                {{ $suppliers->links() }}
            </div>
        @endif
    </div>

    <!-- Deletion Confirmation Modal -->
    <x-modal name="confirm-supplier-deletion" focusable>
        <div class="p-6">
            <h2 class="text-lg font-bold text-blue-900">
                ¿Está seguro de que desea eliminar el proveedor?
            </h2>

            <p class="mt-2 text-sm text-slate-600 font-normal">
                Esta acción archivará el proveedor <span class="font-semibold text-blue-950">"{{ $supplierNameBeingDeleted }}"</span>. Dejará de aparecer en los listados activos y formularios de registro, pero se mantendrá en el historial para fines de auditoría y consulta.
            </p>

            <x-input-error :messages="$errors->get('deletion_error')" class="mt-4" />

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')" class="cursor-pointer">
                    Cancelar
                </x-secondary-button>

                <x-danger-button wire:click="deleteSupplier" class="cursor-pointer">
                    Eliminar
                </x-danger-button>
            </div>
        </div>
    </x-modal>
</div>

<?php

declare(strict_types=1);

use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\SanitaryRegistry;
use App\Models\Laboratory;
use App\Services\SanitaryRegistryService;

new #[Layout('layouts.app')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $softDeleteFilter = 'active';
    public string $statusFilter = 'all';
    public string $laboratoryFilter = 'all';
    public string $expirationStart = '';
    public string $expirationEnd = '';
    public string $sortField = 'registration_number';
    public string $sortDirection = 'asc';

    public ?int $registryIdBeingDeleted = null;
    public string $registryNumberBeingDeleted = '';

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

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingLaboratoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingExpirationStart(): void
    {
        $this->resetPage();
    }

    public function updatingExpirationEnd(): void
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
     * Confirm the sanitary registry deletion.
     */
    public function confirmRegistryDeletion(int $id): void
    {
        $registry = SanitaryRegistry::findOrFail($id);
        $this->registryIdBeingDeleted = $registry->id;
        $this->registryNumberBeingDeleted = $registry->registration_number;
        $this->resetErrorBag();
        $this->dispatch('open-modal', 'confirm-registry-deletion');
    }

    /**
     * Delete/Archive a sanitary registry.
     */
    public function deleteRegistry(SanitaryRegistryService $service): void
    {
        if (!$this->registryIdBeingDeleted) {
            return;
        }

        $registry = SanitaryRegistry::findOrFail($this->registryIdBeingDeleted);

        try {
            $service->delete($registry);

            $this->dispatch('close-modal', 'confirm-registry-deletion');
            $this->reset(['registryIdBeingDeleted', 'registryNumberBeingDeleted']);

            session()->flash('success', 'El registro sanitario ha sido eliminado con éxito.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            foreach ($e->errors() as $key => $messages) {
                foreach ($messages as $message) {
                    $this->addError('deletion_error', $message);
                }
            }
        }
    }

    /**
     * Restore a soft-deleted/archived sanitary registry.
     */
    public function restoreRegistry(int $id, SanitaryRegistryService $service): void
    {
        $registry = SanitaryRegistry::onlyTrashed()->findOrFail($id);
        $service->restore($registry);

        session()->flash('success', 'El registro sanitario ha sido restaurado con éxito.');
    }

    /**
     * Get the sanitary registries with pagination and filters.
     */
    public function with(): array
    {
        $query = SanitaryRegistry::query()->with('laboratory');

        // 1. Soft Delete Filter
        if ($this->softDeleteFilter === 'active') {
            // Default active, standard behavior
        } elseif ($this->softDeleteFilter === 'archived') {
            $query->onlyTrashed();
        } elseif ($this->softDeleteFilter === 'all') {
            $query->withTrashed();
        }

        // 2. Status Filter
        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        // 3. Laboratory Filter
        if ($this->laboratoryFilter !== 'all') {
            $query->where('laboratory_id', $this->laboratoryFilter);
        }

        // 4. Expiration Date Range Filter
        if (!empty($this->expirationStart)) {
            $query->where('expiration_date', '>=', $this->expirationStart);
        }

        if (!empty($this->expirationEnd)) {
            $query->where('expiration_date', '<=', $this->expirationEnd);
        }

        // 5. Search
        if (!empty(trim($this->search))) {
            $query->where('registration_number', 'like', '%' . trim($this->search) . '%');
        }

        // 6. Sorting
        if ($this->sortField === 'laboratory') {
            $query->join('laboratories', 'sanitary_registries.laboratory_id', '=', 'laboratories.id')
                ->select('sanitary_registries.*')
                ->orderBy('laboratories.name', $this->sortDirection);
        } else {
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        return [
            'registries' => $query->paginate(10),
            'laboratories' => Laboratory::orderBy('name')->get(),
        ];
    }
}; ?>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-8 gap-4">
        <div>
            <h1 class="text-3xl font-extrabold text-blue-900 dark:text-white tracking-tight">Registro y Gestión de Registros Sanitarios (INVIMA)</h1>
            <p class="text-sm text-slate-600 dark:text-slate-400 mt-2">
                Administra el catálogo de certificados de registros sanitarios INVIMA para garantizar el cumplimiento de normativas de salud en los medicamentos.
            </p>
        </div>
        <div>
            <a href="{{ route('sanitary-registries.create') }}" wire:navigate
                class="inline-flex items-center bg-blue-900 hover:bg-lime-500 hover:text-blue-950 text-white font-semibold px-5 py-3 rounded-xl shadow-md transition-all duration-200 ease-in-out gap-2 cursor-pointer transform hover:-translate-y-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"></path>
                </svg>
                <span>Nuevo Registro Sanitario</span>
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
        <h2 class="text-lg font-bold text-blue-900 mb-4">Filtrar Registros Sanitarios</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Search field -->
            <div>
                <label for="search" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Búsqueda</label>
                <div class="relative">
                    <input type="text" id="search" wire:model.live.debounce.300ms="search" placeholder="Buscar por número..."
                        class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all uppercase">
                    @if($search)
                        <button type="button" wire:click="$set('search', '')" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    @endif
                </div>
            </div>

            <!-- Laboratory Filter -->
            <div>
                <label for="laboratoryFilter" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Laboratorio Fabricante</label>
                <select id="laboratoryFilter" wire:model.live="laboratoryFilter"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all cursor-pointer">
                    <option value="all">Todos</option>
                    @foreach($laboratories as $lab)
                        <option value="{{ $lab->id }}">{{ $lab->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Record Status Filter -->
            <div>
                <label for="statusFilter" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Estado del Registro</label>
                <select id="statusFilter" wire:model.live="statusFilter"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all cursor-pointer">
                    <option value="all">Todos</option>
                    <option value="valid">Vigente</option>
                    <option value="expired">Vencido</option>
                    <option value="under_renewal">En renovación</option>
                </select>
            </div>

            <!-- Expiration Start Date Filter -->
            <div>
                <label for="expirationStart" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Vencimiento Desde</label>
                <input type="date" id="expirationStart" wire:model.live="expirationStart"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all cursor-pointer">
            </div>

            <!-- Expiration End Date Filter -->
            <div>
                <label for="expirationEnd" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Vencimiento Hasta</label>
                <input type="date" id="expirationEnd" wire:model.live="expirationEnd"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all cursor-pointer">
            </div>

            <!-- Soft Delete filter -->
            <div>
                <label for="softDeleteFilter" class="block text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Visibilidad en Catálogo</label>
                <select id="softDeleteFilter" wire:model.live="softDeleteFilter"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all cursor-pointer">
                    <option value="active">Sí</option>
                    <option value="archived">No</option>
                    <option value="all">Todos</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Card Table -->
    <div class="bg-white border border-slate-100 shadow-sm rounded-2xl overflow-hidden">
        @if(SanitaryRegistry::withTrashed()->count() === 0)
            <div class="p-12 text-center">
                <div class="inline-flex p-4 bg-slate-50 text-slate-400 rounded-full mb-4">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-blue-900 mb-1">No hay registros sanitarios registrados</h3>
                <p class="text-sm text-slate-500 mb-6">Comienza registrando el primer certificado sanitario en el sistema.</p>
                <a href="{{ route('sanitary-registries.create') }}" wire:navigate
                    class="inline-flex items-center bg-blue-900 hover:bg-lime-500 hover:text-blue-950 text-white font-semibold px-4 py-2.5 rounded-lg transition-colors cursor-pointer">
                    Registrar Registro Sanitario
                </a>
            </div>
        @elseif($registries->isEmpty())
            <div class="p-12 text-center">
                <div class="inline-flex p-4 bg-amber-50 text-amber-500 rounded-full mb-4">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-blue-900 mb-1">Sin resultados</h3>
                <p class="text-sm text-slate-500">No se encontraron registros sanitarios que coincidan con los filtros aplicados</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase tracking-wider">
                                <button type="button" wire:click="sortBy('registration_number')" class="flex items-center gap-1 hover:text-blue-700 font-bold uppercase tracking-wider focus:outline-none">
                                    <span>Número de Registro</span>
                                    @if($sortField === 'registration_number')
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
                                <button type="button" wire:click="sortBy('laboratory')" class="flex items-center gap-1 hover:text-blue-700 font-bold uppercase tracking-wider focus:outline-none">
                                    <span>Laboratorio</span>
                                    @if($sortField === 'laboratory')
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
                                <button type="button" wire:click="sortBy('expiration_date')" class="flex items-center gap-1 hover:text-blue-700 font-bold uppercase tracking-wider focus:outline-none">
                                    <span>Vencimiento</span>
                                    @if($sortField === 'expiration_date')
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
                            <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-blue-900 uppercase tracking-wider">Estado</th>
                            <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-blue-900 uppercase tracking-wider">Visibilidad en catálogo</th>
                            <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-blue-900 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-100">
                        @foreach($registries as $registry)
                            <tr class="hover:bg-slate-50/50 transition-colors" wire:key="registry-{{ $registry->id }}">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-blue-950">
                                    {{ $registry->registration_number }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">
                                    {{ $registry->laboratory->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                    {{ \Carbon\Carbon::parse($registry->expiration_date)->format('Y-m-d') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                    @if($registry->status === 'valid')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-lime-50 text-lime-800 border border-lime-200">
                                            Vigente
                                        </span>
                                    @elseif($registry->status === 'expired')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-800 border border-red-200">
                                            Vencido
                                        </span>
                                    @elseif($registry->status === 'under_renewal')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                                            En renovación
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                    @if($registry->trashed())
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-800">
                                            No
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-lime-50 text-lime-800 border border-lime-200">
                                            Sí
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                    @if($registry->trashed())
                                        <button type="button" 
                                            wire:click="restoreRegistry({{ $registry->id }})"
                                            class="inline-flex items-center text-lime-600 hover:text-lime-800 transition-colors gap-1 cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"></path>
                                            </svg>
                                            <span>Restaurar</span>
                                        </button>
                                    @else
                                        <a href="{{ route('sanitary-registries.edit', $registry->id) }}" wire:navigate
                                            class="inline-flex items-center text-blue-600 hover:text-blue-900 transition-colors gap-1 cursor-pointer">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"></path>
                                            </svg>
                                            <span>Editar</span>
                                        </a>

                                        <button type="button" 
                                            wire:click="confirmRegistryDeletion({{ $registry->id }})"
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
                {{ $registries->links() }}
            </div>
        @endif
    </div>

    <!-- Deletion Confirmation Modal -->
    <x-modal name="confirm-registry-deletion" focusable>
        <div class="p-6">
            <h2 class="text-lg font-bold text-blue-900">
                ¿Está seguro de que desea eliminar el registro sanitario?
            </h2>

            <p class="mt-2 text-sm text-slate-600 font-normal">
                Esta acción archivará el registro sanitario <span class="font-semibold text-blue-950">"{{ $registryNumberBeingDeleted }}"</span>. Dejará de aparecer en los listados activos y formularios de registro, pero se mantendrá en el historial para fines de auditoría y consulta.
            </p>

            <x-input-error :messages="$errors->get('deletion_error')" class="mt-4" />

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')" class="cursor-pointer">
                    Cancelar
                </x-secondary-button>

                <x-danger-button wire:click="deleteRegistry" class="cursor-pointer">
                    Eliminar
                </x-danger-button>
            </div>
        </div>
    </x-modal>
</div>

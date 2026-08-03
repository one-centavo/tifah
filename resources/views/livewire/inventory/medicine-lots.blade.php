<?php

declare(strict_types=1);

use App\Models\Medicine;
use App\Models\Lot;
use App\Services\LotService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public Medicine $medicine;

    // Deletion Modal
    public ?int $lotIdBeingDeleted = null;

    public string $lotBatchBeingDeleted = '';

    public function mount(Medicine $medicine): void
    {
        $this->medicine = $medicine;
    }

    /**
     * Confirm lot deletion.
     */
    public function confirmLotDeletion(int $id): void
    {
        $lot = Lot::findOrFail($id);
        $this->lotIdBeingDeleted = $lot->id;
        $this->lotBatchBeingDeleted = $lot->batch_number;
        $this->resetErrorBag();
        $this->dispatch('open-modal', 'confirm-lot-deletion');
    }

    /**
     * Delete lot (logical soft delete).
     */
    public function deleteLot(LotService $lotService): void
    {
        if (! $this->lotIdBeingDeleted) {
            return;
        }

        $lot = Lot::findOrFail($this->lotIdBeingDeleted);
        $lotService->delete($lot);

        $this->dispatch('close-modal', 'confirm-lot-deletion');
        $this->reset(['lotIdBeingDeleted', 'lotBatchBeingDeleted']);

        session()->flash('success', 'El lote ha sido eliminado con éxito.');
    }

    public function with(): array
    {
        $lots = $this->medicine->lots()
            ->with(['purchaseOrder.supplier'])
            ->orderBy('expiration_date', 'asc')
            ->get();

        return [
            'lots' => $lots,
        ];
    }
}; ?>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <!-- Back to Dashboard -->
    <div class="mb-6">
        <a href="{{ route('inventory.index') }}" wire:navigate
            class="inline-flex items-center gap-2 text-sm font-bold text-blue-900 hover:text-lime-500 transition-colors">
            <x-tabler-arrow-left class="w-4 h-4" />
            <span>Volver al Inventario</span>
        </a>
    </div>

    <!-- Alert Success -->
    @if (session()->has('success'))
        <div class="mb-6 p-4 bg-lime-50 border border-lime-200 text-lime-800 rounded-xl flex items-center shadow-sm animate-pulse" role="alert" id="success-alert">
            <svg class="w-5 h-5 mr-2 text-lime-600 shrink-0" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            <span class="font-medium">{{ session('success') }}</span>
        </div>
    @endif

    <!-- Header / Medicine info card -->
    <div class="bg-white border border-slate-100 shadow-sm rounded-2xl p-6 mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <span class="text-xs uppercase font-extrabold tracking-wider text-slate-400">Medicamento seleccionado</span>
                <h1 class="text-3xl font-extrabold text-blue-900 dark:text-white tracking-tight mt-1">{{ $medicine->name }}</h1>
                <div class="flex flex-wrap gap-x-6 gap-y-2 mt-3 text-sm text-slate-600 dark:text-slate-400">
                    <div>
                        <strong class="font-semibold text-slate-800">Nombre Genérico:</strong> {{ $medicine->generic_name ?: 'N/A' }}
                    </div>
                    <div>
                        <strong class="font-semibold text-slate-800">Concentración:</strong> {{ $medicine->concentration_formatted }}
                    </div>
                    <div>
                        <strong class="font-semibold text-slate-800">Presentación:</strong> {{ $medicine->presentation }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lots Table -->
    <div class="bg-white border border-slate-100 shadow-sm rounded-2xl overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
            <h2 class="text-lg font-bold text-blue-900">Listado de Lotes</h2>
        </div>

        @if($lots->isEmpty())
            <div class="p-12 text-center">
                <div class="inline-flex p-4 bg-slate-50 text-slate-400 rounded-full mb-4">
                    <x-tabler-packages class="w-12 h-12" />
                </div>
                <h3 class="text-lg font-bold text-blue-900 mb-1">Sin lotes</h3>
                <p class="text-sm text-slate-500">Este medicamento no cuenta con ningún lote registrado actualmente.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase tracking-wider">Número de Lote</th>
                            <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-blue-900 uppercase tracking-wider">Fecha de Vencimiento</th>
                            <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-blue-900 uppercase tracking-wider">Existencias Actuales</th>
                            <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-blue-900 uppercase tracking-wider">Cantidad Inicial</th>
                            <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-blue-900 uppercase tracking-wider">Costo Unit. de Compra</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase tracking-wider">Proveedor</th>
                            <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-blue-900 uppercase tracking-wider">Estado</th>
                            <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-blue-900 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach($lots as $lot)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-mono bg-slate-100 text-slate-800 px-2 py-1 rounded font-medium">{{ $lot->batch_number }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                    @php
                                        $daysLeft = now()->diffInDays(\Carbon\Carbon::parse($lot->expiration_date), false);
                                    @endphp
                                    @if($daysLeft < 0)
                                        <span class="text-red-600 font-bold" title="Vencido">{{ \Carbon\Carbon::parse($lot->expiration_date)->format('d/m/Y') }}</span>
                                    @elseif($daysLeft <= 90)
                                        <span class="text-amber-600 font-bold" title="Por vencer en {{ $daysLeft }} días">{{ \Carbon\Carbon::parse($lot->expiration_date)->format('d/m/Y') }}</span>
                                    @else
                                        <span class="text-slate-700">{{ \Carbon\Carbon::parse($lot->expiration_date)->format('d/m/Y') }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-semibold text-slate-800">
                                    {{ $lot->current_quantity }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-slate-700">
                                    {{ $lot->initial_quantity }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-slate-800 font-mono">
                                    ${{ number_format($lot->unit_purchase_price, 2) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-700">
                                    {{ $lot->purchaseOrder->supplier->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($lot->status === 'active')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-lime-50 text-lime-700 border border-lime-100">
                                            Activo
                                        </span>
                                    @elseif($lot->status === 'blocked')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-100">
                                            Bloqueado
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-700 border border-red-100">
                                            Dañado
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @if($lot->deleted_at)
                                        <div class="text-xs text-slate-400 text-right">
                                            <span>Eliminado por {{ $lot->deleter->name ?? 'Sistema' }}</span><br>
                                            <span>el {{ \Carbon\Carbon::parse($lot->deleted_at)->format('d/m/Y H:i') }}</span>
                                        </div>
                                    @else
                                        <button type="button" wire:click="confirmLotDeletion({{ $lot->id }})"
                                            class="inline-flex items-center text-red-600 hover:text-red-900 transition-colors gap-1 cursor-pointer">
                                            <x-tabler-trash class="w-4 h-4" />
                                            <span>Eliminar</span>
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Deletion Confirmation Modal -->
    <x-modal name="confirm-lot-deletion" focusable>
        <div class="p-6">
            <h2 class="text-lg font-bold text-blue-900">
                ¿Está seguro de que desea eliminar el lote?
            </h2>

            <p class="mt-2 text-sm text-slate-600 font-normal">
                Esta acción archivará el lote <span class="font-semibold text-blue-950 font-mono">"{{ $lotBatchBeingDeleted }}"</span>. Dejará de sumarse a las existencias activas para la venta, pero la información histórica quedará almacenada para futuras auditorías de trazabilidad.
            </p>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')" class="cursor-pointer">
                    Cancelar
                </x-secondary-button>

                <x-danger-button wire:click="deleteLot" class="cursor-pointer">
                    Eliminar
                </x-danger-button>
            </div>
        </div>
    </x-modal>
</div>

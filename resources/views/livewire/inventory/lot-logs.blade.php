<?php

declare(strict_types=1);

use App\Models\Lot;
use App\Models\User;
use App\Models\InventoryMovement;
use App\Services\LotService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public Lot $lot;

    // Selected movement state
    public ?int $selectedMovementId = null;
    public ?int $newQuantity = null;
    public string $reason = '';
    public string $observations = '';

    public function mount(Lot $lot): void
    {
        $this->lot = $lot->load(['purchaseOrder.supplier', 'medicine']);
    }

    /**
     * Load a movement to adjust.
     */
    public function selectMovementForAdjustment(int $movementId): void
    {
        // Enforce role check in backend too
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Acción no autorizada.');
        }

        $movement = InventoryMovement::findOrFail($movementId);
        
        $this->selectedMovementId = $movement->id;
        $this->newQuantity = $movement->quantity;
        $this->reason = '';
        $this->observations = '';
        
        $this->resetErrorBag();
        $this->dispatch('open-modal', 'perform-adjustment');
    }

    protected function rules(): array
    {
        return [
            'newQuantity' => ['required', 'integer', 'min:0'],
            'reason' => ['required', 'string', 'in:Error de digitación,Unidad dañada,Unidad faltante,Otro'],
            'observations' => ['required', 'string', 'max:500'],
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'newQuantity' => 'nueva cantidad',
            'reason' => 'motivo del ajuste',
            'observations' => 'observaciones adicionales',
        ];
    }

    /**
     * Save the stock adjustment for the selected movement.
     */
    public function saveAdjustment(LotService $lotService): void
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Acción no autorizada.');
        }

        $this->validate();

        $movement = InventoryMovement::findOrFail($this->selectedMovementId);

        // Perform the adjustment
        $lotService->adjustMovement(
            $movement,
            (int) $this->newQuantity,
            $this->reason,
            $this->observations,
            auth()->id()
        );

        $this->dispatch('close-modal', 'perform-adjustment');
        
        // Reset state
        $this->reset(['selectedMovementId', 'newQuantity', 'reason', 'observations']);
        $this->lot->refresh();

        session()->flash('success', 'El ajuste de movimiento ha sido registrado con éxito.');
    }

    public function with(): array
    {
        // Sort grouped: adjustments (linked via reference_id) are grouped directly below their original parent movement
        $movements = $this->lot->inventoryMovements()
            ->with('creator')
            ->orderByRaw("CASE WHEN type = 'adjustment' THEN reference_id ELSE id END ASC")
            ->orderBy('id', 'ASC')
            ->get();

        return [
            'movements' => $movements,
        ];
    }
}; ?>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <!-- Back to Medicine Lots (Level 2) -->
    <div class="mb-6">
        <a href="{{ route('inventory.medicine-lots', $lot->medicine_id) }}" wire:navigate
            class="inline-flex items-center gap-2 text-sm font-bold text-blue-900 hover:text-lime-500 transition-colors">
            <x-tabler-arrow-left class="w-4 h-4" />
            <span>Volver a Lotes del Medicamento</span>
        </a>
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

    <!-- Context Header / Lot info card -->
    <div class="bg-white border border-slate-100 shadow-sm rounded-2xl p-6 mb-8">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <span class="text-xs uppercase font-extrabold tracking-wider text-slate-400">Historial y Trazabilidad del Lote</span>
                <div class="flex items-center gap-3 mt-1">
                    <h1 class="text-3xl font-extrabold text-blue-900 tracking-tight">Lote: {{ $lot->batch_number }}</h1>
                </div>
                <div class="flex flex-wrap gap-x-6 gap-y-2 mt-3 text-sm text-slate-600">
                    <div>
                        <strong class="font-semibold text-slate-800">Medicamento:</strong> {{ $lot->medicine->name }} ({{ $lot->medicine->generic_name ?: 'Sin nombre genérico' }})
                    </div>
                    <div>
                        <strong class="font-semibold text-slate-800">Presentación:</strong> {{ $lot->medicine->presentation }}
                    </div>
                    <div>
                        <strong class="font-semibold text-slate-800">Vencimiento:</strong> {{ \Carbon\Carbon::parse($lot->expiration_date)->format('d/m/Y') }}
                    </div>
                    <div>
                        <strong class="font-semibold text-slate-800">Existencias Físicas Totales:</strong> <span class="font-bold text-blue-900">{{ $lot->current_quantity }}</span>
                    </div>
                    <div>
                        <strong class="font-semibold text-slate-800">Proveedor:</strong> {{ $lot->purchaseOrder->supplier->name ?? 'N/A' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Movements Log (Kardex) -->
    <div class="bg-white border border-slate-100 shadow-sm rounded-2xl overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-slate-50/50">
            <h2 class="text-lg font-bold text-blue-900">Kardex - Historial de Movimientos</h2>
        </div>

        @if($movements->isEmpty())
            <div class="p-12 text-center">
                <div class="inline-flex p-4 bg-slate-50 text-slate-400 rounded-full mb-4">
                    <x-tabler-history class="w-12 h-12" />
                </div>
                <h3 class="text-lg font-bold text-blue-900 mb-1">Sin movimientos</h3>
                <p class="text-sm text-slate-500">Este lote no cuenta con movimientos registrados.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase tracking-wider">Fecha y Hora</th>
                            <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-blue-900 uppercase tracking-wider">Movimiento</th>
                            <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-blue-900 uppercase tracking-wider">Saldo Anterior</th>
                            <th scope="col" class="px-6 py-4 text-center text-xs font-bold text-blue-900 uppercase tracking-wider">Nuevo Saldo</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase tracking-wider">Concepto / Motivo</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase tracking-wider">Observaciones</th>
                            <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-blue-900 uppercase tracking-wider">Usuario Responsable</th>
                            <th scope="col" class="px-6 py-4 text-right text-xs font-bold text-blue-900 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @foreach($movements as $movement)
                            <tr class="hover:bg-slate-50/50 transition-colors {{ $movement->type === 'adjustment' ? 'bg-slate-50/30' : '' }}">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600 font-mono">
                                    <div class="flex items-center gap-1.5">
                                        @if($movement->type === 'adjustment')
                                            <x-tabler-corner-down-right class="w-4 h-4 text-slate-400 shrink-0" />
                                        @endif
                                        <span>{{ \Carbon\Carbon::parse($movement->created_at)->format('d/m/Y H:i:s') }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-bold">
                                    @if($movement->quantity > 0)
                                        <span class="text-lime-600">+{{ $movement->quantity }}</span>
                                    @elseif($movement->quantity < 0)
                                        <span class="text-red-600">{{ $movement->quantity }}</span>
                                    @else
                                        <span class="text-slate-500">0</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-slate-500 font-mono">
                                    {{ $movement->previous_balance }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-slate-800 font-semibold font-mono">
                                    {{ $movement->new_balance }}
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-700">
                                    @php
                                        $displayReason = match($movement->type) {
                                            'entry' => 'Registro Inicial',
                                            'exit' => 'Salida por Venta',
                                            'adjustment' => 'Ajuste por Error - ' . ($movement->adjustment_reason ?? 'Otro'),
                                            default => $movement->concept,
                                        };
                                    @endphp
                                    <span class="font-semibold text-slate-800">{{ $displayReason }}</span>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-600 max-w-xs truncate" title="{{ $movement->observations ?: $movement->concept }}">
                                    @if($movement->type === 'adjustment')
                                        <span class="italic text-slate-500">{{ $movement->observations }}</span>
                                    @else
                                        <span class="text-slate-400 font-mono text-xs">Ref: #{{ $movement->reference_id }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                    <div class="font-medium text-slate-900">{{ $movement->creator->name ?? 'Sistema' }}</div>
                                    <div class="text-xs text-slate-400 font-mono">ID: {{ $movement->created_by ?? 'N/A' }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    @if(auth()->user()->isAdmin())
                                        @if($movement->type !== 'adjustment')
                                            <button type="button" wire:click="selectMovementForAdjustment({{ $movement->id }})"
                                                class="inline-flex items-center text-blue-900 hover:text-lime-600 transition-colors gap-1 cursor-pointer font-bold">
                                                <x-tabler-adjustments class="w-4 h-4" />
                                                <span>Ajustar</span>
                                            </button>
                                        @else
                                            <span class="text-xs text-slate-400 italic">No ajustable</span>
                                        @endif
                                    @else
                                        <span class="text-xs text-slate-400 italic">Sólo Admin</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Stock Adjustment Modal -->
    <x-modal name="perform-adjustment" focusable>
        <div class="p-6">
            <h2 class="text-xl font-bold text-blue-900 border-b border-slate-100 pb-3">
                Registrar Ajuste de Movimiento #{{ $selectedMovementId }}
            </h2>

            @if($selectedMovementId)
                @php
                    $targetMovement = $movements->firstWhere('id', $selectedMovementId);
                @endphp
                
                @if($targetMovement)
                    <div class="mt-4 p-4 bg-slate-50 border border-slate-100 rounded-xl space-y-2 text-sm text-slate-700">
                        <div>
                            <strong class="font-semibold text-slate-800">Fecha del registro original:</strong> 
                            {{ \Carbon\Carbon::parse($targetMovement->created_at)->format('d/m/Y H:i:s') }}
                        </div>
                        <div>
                            <strong class="font-semibold text-slate-800">Tipo de movimiento:</strong> 
                            {{ $targetMovement->type === 'entry' ? 'Registro Inicial' : 'Salida por Venta' }}
                        </div>
                        <div>
                            <strong class="font-semibold text-slate-800">Cantidad registrada originalmente:</strong> 
                            <span class="font-mono font-bold">{{ $targetMovement->quantity }} unidades</span>
                        </div>
                    </div>
                @endif
            @endif

            <form wire:submit.prevent="saveAdjustment" class="mt-6 space-y-4">
                <div>
                    <x-input-label for="newQuantity" value="Cantidad Corregida (Valor Real)" class="font-semibold text-slate-700" />
                    <x-text-input type="number" id="newQuantity" wire:model="newQuantity" class="mt-1 block w-full rounded-xl" min="0" placeholder="Ingrese la cantidad real corregida" />
                    <x-input-error :messages="$errors->get('newQuantity')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="reason" value="Motivo del Ajuste" class="font-semibold text-slate-700" />
                    <select id="reason" wire:model="reason" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-slate-800 text-sm py-2 px-3">
                        <option value="">Seleccione un motivo...</option>
                        <option value="Error de digitación">Error de digitación</option>
                        <option value="Unidad dañada">Unidad dañada</option>
                        <option value="Unidad faltante">Unidad faltante</option>
                        <option value="Otro">Otro</option>
                    </select>
                    <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="observations" value="Observaciones Adicionales (Breve Descripción)" class="font-semibold text-slate-700" />
                    <textarea id="observations" wire:model="observations" rows="3" class="mt-1 block w-full rounded-xl border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-slate-800 text-sm" placeholder="Describa qué causó el error y justifique la corrección..."></textarea>
                    <x-input-error :messages="$errors->get('observations')" class="mt-2" />
                </div>

                <div class="mt-6 flex justify-end gap-3 border-t border-slate-100 pt-4">
                    <x-secondary-button x-on:click="$dispatch('close')" class="cursor-pointer">
                        Cancelar
                    </x-secondary-button>

                    <x-primary-button type="submit" class="bg-blue-900 hover:bg-lime-500 hover:text-blue-950 cursor-pointer">
                        Guardar Ajuste
                    </x-primary-button>
                </div>
            </form>
        </div>
    </x-modal>
</div>

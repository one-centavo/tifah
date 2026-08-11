<?php

declare(strict_types=1);

use App\Models\Bill;
use App\Services\BillService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithPagination;


new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    public string $search = '';
    public string $status = 'all';
    public string $paymentMethod = 'all';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    // Detail Modal State
    public ?int $viewingBillId = null;
    public bool $showDetailModal = false;

    // Annulment Modal State
    public ?int $annullingBillId = null;
    public string $annulmentReason = '';
    public bool $showAnnulModal = false;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingPaymentMethod(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function openDetailModal(int $billId): void
    {
        $this->viewingBillId = $billId;
        $this->showDetailModal = true;
    }

    public function closeDetailModal(): void
    {
        $this->showDetailModal = false;
        $this->viewingBillId = null;
    }

    public function getViewingBillProperty(): ?Bill
    {
        return $this->viewingBillId
            ? Bill::with(['customer', 'creator', 'annuller', 'details.lot.medicine.container', 'details.lot.medicine.contentUnit'])->find($this->viewingBillId)
            : null;
    }

    public function openAnnulModal(int $billId): void
    {
        $this->annullingBillId = $billId;
        $this->annulmentReason = '';
        $this->resetErrorBag();
        $this->showAnnulModal = true;
    }

    public function closeAnnulModal(): void
    {
        $this->showAnnulModal = false;
        $this->annullingBillId = null;
        $this->annulmentReason = '';
    }

    public function getAnnullingBillProperty(): ?Bill
    {
        return $this->annullingBillId ? Bill::find($this->annullingBillId) : null;
    }

    public function annulBill(BillService $billService): void
    {
        $this->validate([
            'annulmentReason' => ['required', 'string', 'min:5', 'max:500'],
        ], [
            'annulmentReason.required' => 'El motivo de anulación es obligatorio.',
            'annulmentReason.min' => 'El motivo debe contener al menos 5 caracteres.',
        ]);

        if (! $this->annullingBillId) {
            return;
        }

        $bill = Bill::findOrFail($this->annullingBillId);

        try {
            $billService->annulBill($bill, $this->annulmentReason, auth()->id());

            $this->closeAnnulModal();
            session()->flash('success', 'La factura ha sido anulada con éxito y los productos fueron devueltos a sus lotes de origen.');
        } catch (ValidationException $e) {
            foreach ($e->errors() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }
        }
    }

    public function with(): array
    {
        $query = Bill::query()->with(['customer', 'creator']);

        if (! empty($this->search)) {
            $searchTerm = trim($this->search);
            $query->where(function ($q) use ($searchTerm) {
                $q->where('invoice_number', 'like', '%' . $searchTerm . '%')
                    ->orWhereHas('customer', function ($cq) use ($searchTerm) {
                        $cq->where('name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('nit', 'like', '%' . $searchTerm . '%');
                    });
            });
        }

        if ($this->status !== 'all') {
            $query->where('status', $this->status);
        }

        if ($this->paymentMethod !== 'all') {
            $query->where('payment_method', $this->paymentMethod);
        }

        if (! empty($this->dateFrom)) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if (! empty($this->dateTo)) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        return [
            'bills' => $query->orderBy($this->sortField, $this->sortDirection)->paginate(15),
        ];
    }

}; ?>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-blue-900">Facturas y Salidas de Mercancía</h1>
            <p class="text-sm text-slate-600 mt-1">
                Consulta el histórico de ventas legalizadas, descarga comprobantes PDF y gestiona anulaciones con restitución de inventario.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('sales.create') }}" wire:navigate
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-900 hover:bg-blue-800 text-white text-sm font-bold rounded-xl shadow-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path>
                </svg>
                <span>Nueva Venta</span>
            </a>
        </div>
    </div>

    <!-- Flash Message -->
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-xl flex items-center gap-3 text-green-900 text-sm font-medium">
            <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Filters Bar -->
    <div class="bg-white border border-slate-200 shadow-sm rounded-2xl p-5 mb-6 space-y-4 min-w-0">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-4">
            
            <!-- Search -->
            <div class="lg:col-span-2">
                <label class="text-xs font-semibold text-slate-700 mb-1 block">Buscar Factura o Cliente</label>
                <div class="relative">
                    <input type="text" wire:model.live.debounce.300ms="search"
                        placeholder="N° Factura (FAC-000001), NIT o Cliente..."
                        class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-blue-900">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Status Filter -->
            <div>
                <label class="text-xs font-semibold text-slate-700 mb-1 block">Estado</label>
                <select wire:model.live="status"
                    class="w-full py-2 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-blue-900">
                    <option value="all">Todos los Estados</option>
                    <option value="active">Activas</option>
                    <option value="annulled">Anuladas</option>
                </select>
            </div>

            <!-- Payment Method Filter -->
            <div>
                <label class="text-xs font-semibold text-slate-700 mb-1 block">Forma de Pago</label>
                <select wire:model.live="paymentMethod"
                    class="w-full py-2 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-blue-900">
                    <option value="all">Todas las Formas</option>
                    <option value="cash">Efectivo</option>
                    <option value="transfer">Transferencia</option>
                    <option value="credit">Crédito</option>
                </select>
            </div>

            <!-- Date From -->
            <div>
                <label class="text-xs font-semibold text-slate-700 mb-1 block">Desde</label>
                <input type="date" wire:model.live="dateFrom"
                    class="w-full py-2 px-3 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:ring-1 focus:ring-blue-900">
            </div>

        </div>
    </div>

    <!-- Invoices Table -->
    <div class="bg-white border border-slate-200 shadow-sm rounded-2xl overflow-hidden min-w-0">
        <div class="overflow-x-auto min-w-0">
            <table class="w-full text-left text-sm border-collapse">
                <thead class="bg-slate-50 border-b border-slate-200 text-xs font-bold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4 cursor-pointer" wire:click="sortBy('invoice_number')">
                            <div class="flex items-center gap-1">
                                <span>N° Factura</span>
                                @if($sortField === 'invoice_number')
                                    <span class="text-blue-900 font-bold">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="py-3 px-4 cursor-pointer" wire:click="sortBy('created_at')">
                            <div class="flex items-center gap-1">
                                <span>Fecha y Hora</span>
                                @if($sortField === 'created_at')
                                    <span class="text-blue-900 font-bold">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="py-3 px-4">Cliente</th>
                        <th class="py-3 px-4">Forma de Pago</th>
                        <th class="py-3 px-4 text-right cursor-pointer" wire:click="sortBy('total_amount')">
                            <div class="flex items-center justify-end gap-1">
                                <span>Total</span>
                                @if($sortField === 'total_amount')
                                    <span class="text-blue-900 font-bold">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                @endif
                            </div>
                        </th>
                        <th class="py-3 px-4 text-center">Estado</th>
                        <th class="py-3 px-4 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($bills as $bill)
                        <tr class="hover:bg-slate-50/60 transition">
                            <!-- Invoice Number -->
                            <td class="py-3.5 px-4 font-mono font-bold text-xs text-blue-900">
                                {{ $bill->invoice_number ?: 'FAC-' . str_pad((string) $bill->id, 6, '0', STR_PAD_LEFT) }}
                            </td>

                            <!-- Date / Time -->
                            <td class="py-3.5 px-4 text-xs text-slate-600 whitespace-nowrap">
                                {{ $bill->created_at->format('d/m/Y H:i') }}
                            </td>

                            <!-- Customer -->
                            <td class="py-3.5 px-4">
                                <div class="font-semibold text-xs text-slate-900">
                                    {{ $bill->customer?->name ?? 'Cliente Desconocido' }}
                                </div>
                                <div class="text-[11px] text-slate-500 font-mono">
                                    NIT: {{ $bill->customer?->nit }}-{{ $bill->customer?->dv }}
                                </div>
                            </td>

                            <!-- Payment Method -->
                            <td class="py-3.5 px-4 text-xs">
                                @if($bill->payment_method === 'cash')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-emerald-100 text-emerald-800">
                                        Efectivo
                                    </span>
                                @elseif($bill->payment_method === 'transfer')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-blue-100 text-blue-800">
                                        Transferencia
                                    </span>
                                @elseif($bill->payment_method === 'credit')
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-medium bg-amber-100 text-amber-800">
                                        Crédito (Vence: {{ $bill->payment_due_date?->format('d/m/Y') ?? 'N/A' }})
                                    </span>
                                @else
                                    <span class="text-slate-500">{{ ucfirst($bill->payment_method) }}</span>
                                @endif
                            </td>

                            <!-- Total -->
                            <td class="py-3.5 px-4 text-right font-bold text-xs text-slate-900">
                                ${{ number_format((float) $bill->total_amount, 0, ',', '.') }}
                            </td>

                            <!-- Status -->
                            <td class="py-3.5 px-4 text-center">
                                @if($bill->status === 'active')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                        Activa
                                    </span>
                                @elseif($bill->status === 'annulled')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                        Anulada
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-800">
                                        {{ ucfirst($bill->status) }}
                                    </span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="py-3.5 px-4 text-center whitespace-nowrap">
                                <div class="flex items-center justify-center gap-1.5">
                                    <!-- Ver Detalle -->
                                    <button type="button"
                                        wire:click="openDetailModal({{ $bill->id }})"
                                        class="p-1.5 text-blue-700 hover:text-blue-900 hover:bg-blue-50 rounded-lg transition cursor-pointer"
                                        title="Ver Detalle de Factura">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </button>

                                    <!-- Descargar PDF -->
                                    <a href="{{ route('bills.pdf', $bill->id) }}" target="_blank"
                                        class="p-1.5 text-slate-600 hover:text-slate-900 hover:bg-slate-100 rounded-lg transition"
                                        title="Descargar Comprobante PDF">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                    </a>

                                    <!-- Anular Factura -->
                                    @if($bill->status === 'active')
                                        <button type="button"
                                            wire:click="openAnnulModal({{ $bill->id }})"
                                            class="p-1.5 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition cursor-pointer"
                                            title="Anular Factura">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-slate-400 text-sm">
                                No se encontraron facturas con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($bills->hasPages())
            <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50">
                {{ $bills->links() }}
            </div>
        @endif
    </div>

    <!-- MODAL: Invoice Detail -->
    @if($showDetailModal && $this->viewingBill)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-2xl w-full shadow-2xl border border-slate-200 overflow-hidden"
                @click.outside="$wire.closeDetailModal()">
                
                <div class="px-6 py-4 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="font-mono font-bold text-sm text-blue-900">
                                {{ $this->viewingBill->invoice_number ?: 'FAC-' . str_pad((string) $this->viewingBill->id, 6, '0', STR_PAD_LEFT) }}
                            </span>
                            @if($this->viewingBill->status === 'active')
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800">Activa</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">Anulada</span>
                            @endif
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Emitida el {{ $this->viewingBill->created_at->format('d/m/Y H:i') }} por {{ $this->viewingBill->creator?->name ?? 'Usuario' }}
                        </p>
                    </div>

                    <button type="button" wire:click="closeDetailModal" class="text-slate-400 hover:text-slate-600 cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <!-- Customer Information Banner -->
                    <div class="p-3 bg-blue-50/50 border border-blue-100 rounded-xl text-xs space-y-1">
                        <div class="font-bold text-slate-900">{{ $this->viewingBill->customer?->name }}</div>
                        <div class="text-slate-500">NIT: {{ $this->viewingBill->customer?->nit }}-{{ $this->viewingBill->customer?->dv }} &bull; {{ $this->viewingBill->customer?->address }}, {{ $this->viewingBill->customer?->city }}</div>
                        <div class="text-slate-500">Forma de Pago: <span class="font-semibold text-slate-700">{{ ucfirst($this->viewingBill->payment_method) }}</span></div>
                    </div>

                    @if($this->viewingBill->status === 'annulled')
                        <div class="p-3 bg-red-50 border border-red-200 rounded-xl text-xs text-red-900">
                            <div class="font-bold">Factura Anulada:</div>
                            <div>Motivo: {{ $this->viewingBill->annulled_reason }}</div>
                            <div class="text-[11px] text-red-700 mt-0.5">Anulada por {{ $this->viewingBill->annuller?->name ?? 'Sistema' }} el {{ $this->viewingBill->annulled_at?->format('d/m/Y H:i') }}</div>
                        </div>
                    @endif

                    <!-- Detail Table -->
                    <div class="overflow-x-auto border border-slate-100 rounded-xl">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-50 font-bold text-slate-600 border-b">
                                <tr>
                                    <th class="py-2 px-3">Producto</th>
                                    <th class="py-2 px-3">Lote</th>
                                    <th class="py-2 px-3">Vence</th>
                                    <th class="py-2 px-3 text-center">Cant.</th>
                                    <th class="py-2 px-3 text-right">Precio Unit.</th>
                                    <th class="py-2 px-3 text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($this->viewingBill->details as $detail)
                                    <tr>
                                        <td class="py-2 px-3 font-semibold">{{ $detail->lot?->medicine?->name ?? 'Medicamento' }}</td>
                                        <td class="py-2 px-3 font-mono">{{ $detail->lot?->batch_number ?? 'N/A' }}</td>
                                        <td class="py-2 px-3">{{ $detail->lot?->expiration_date ?? 'N/A' }}</td>
                                        <td class="py-2 px-3 text-center font-bold">{{ $detail->quantity }}</td>
                                        <td class="py-2 px-3 text-right">${{ number_format((float) $detail->unit_price, 0, ',', '.') }}</td>
                                        <td class="py-2 px-3 text-right font-bold">${{ number_format((float) $detail->subtotal, 0, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="text-right pt-2 border-t font-bold text-base text-blue-900">
                        Total Facturado: ${{ number_format((float) $this->viewingBill->total_amount, 0, ',', '.') }}
                    </div>
                </div>

                <div class="px-6 py-3 bg-slate-50 border-t flex items-center justify-between">
                    <a href="{{ route('bills.pdf', $this->viewingBill->id) }}" target="_blank"
                        class="px-4 py-2 bg-blue-900 text-white font-bold text-xs rounded-xl hover:bg-blue-800 transition">
                        Descargar Comprobante PDF
                    </a>

                    <button type="button" wire:click="closeDetailModal"
                        class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 cursor-pointer">
                        Cerrar
                    </button>
                </div>

            </div>
        </div>
    @endif

    <!-- MODAL: Annul Invoice Confirmation -->
    @if($showAnnulModal && $this->annullingBill)
        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl max-w-md w-full shadow-2xl border border-slate-200 p-6 space-y-4"
                @click.outside="$wire.closeAnnulModal()">
                
                <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>

                <div class="text-center">
                    <h3 class="text-base font-bold text-slate-900">¿Desea anular esta factura?</h3>
                    <p class="text-xs text-slate-500 mt-1">
                        Factura <span class="font-bold text-blue-900">{{ $this->annullingBill->invoice_number }}</span> por valor de <span class="font-bold">${{ number_format((float) $this->annullingBill->total_amount, 0, ',', '.') }}</span>.
                    </p>
                    <p class="text-xs text-amber-700 bg-amber-50 p-2 rounded-lg mt-2 font-medium">
                        ⚠️ Al anular la factura, todas las unidades vendidas serán devueltas automáticamente a sus lotes de origen y quedarán disponibles para nuevas ventas.
                    </p>
                </div>

                <form wire:submit="annulBill" class="space-y-3">
                    <div>
                        <label class="text-xs font-bold text-slate-700 block mb-1">
                            Motivo de la Anulación *
                        </label>
                        <textarea wire:model="annulmentReason" rows="3" required
                            placeholder="Describa la razón por la cual se anula la venta (ej. Error en digitación de medicamentos, cliente canceló el pedido)..."
                            class="w-full text-xs p-2.5 border border-slate-200 rounded-xl focus:ring-1 focus:ring-red-500 bg-slate-50"></textarea>
                        @error('annulmentReason')
                            <span class="text-xs text-red-600 font-medium">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-2">
                        <button type="button" wire:click="closeAnnulModal"
                            class="px-4 py-2 text-xs font-semibold text-slate-600 hover:text-slate-800 cursor-pointer">
                            Cancelar
                        </button>

                        <button type="submit"
                            class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-bold text-xs rounded-xl shadow-sm transition cursor-pointer">
                            Confirmar Anulación y Devolver Stock
                        </button>
                    </div>
                </form>

            </div>
        </div>
    @endif
</div>


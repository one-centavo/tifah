<?php

declare(strict_types=1);

use App\Services\ExpirationAlertService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] class extends Component
{
    use WithPagination;

    #[Url(as: 'urgency')]
    public string $urgencyFilter = '';

    #[Url(as: 'search')]
    public string $search = '';

    public string $dismissMessage = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function setUrgencyFilter(string $tier): void
    {
        $this->urgencyFilter = $tier;
        $this->resetPage();
    }

    public function dismissToday(ExpirationAlertService $service): void
    {
        $user = auth()->user();

        if ($user) {
            $service->dismissAlertsForToday($user);
            $this->dismissMessage = 'Las notificaciones visuales del día han sido marcadas como leídas. Volverán a aparecer mañana si los lotes continúan activos.';
            $this->dispatch('expiration-alerts-updated');
        }
    }

    public function with(ExpirationAlertService $service): array
    {
        $metrics = $service->getAlertSummaryMetrics();
        $isDismissedToday = $service->isDismissedTodayForUser(auth()->user());

        $lotsQuery = $service->getExpiringLotsQuery(
            maxDays: 90,
            urgencyFilter: $this->urgencyFilter ?: null,
            search: $this->search ?: null
        );

        $lots = $lotsQuery->paginate(15);

        return [
            'metrics' => $metrics,
            'isDismissedToday' => $isDismissedToday,
            'lots' => $lots,
        ];
    }
}; ?>

<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
        <!-- Breadcrumbs & Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <nav class="flex text-sm text-gray-500 mb-1" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-2">
                        <li class="inline-flex items-center">
                            <a href="{{ route('dashboard') }}" class="hover:text-gray-700" wire:navigate>
                                Dashboard
                            </a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <a href="{{ route('inventory.index') }}" class="ms-1 hover:text-gray-700" wire:navigate>
                                    Inventario
                                </a>
                            </div>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="ms-1 font-semibold text-gray-800">Alertas de Vencimiento</span>
                            </div>
                        </li>
                    </ol>
                </nav>
                <h1 class="text-2xl font-bold tracking-tight text-gray-900">
                    Control y Gestión de Alertas de Vencimiento
                </h1>
                <p class="text-sm text-gray-500 mt-0.5">
                    Monitoreo preventivo de lotes farmacéuticos próximos a expirar a 30, 60 y 90 días.
                </p>
            </div>

            <!-- Header Action Buttons -->
            <div class="flex flex-wrap items-center gap-3">
                <button
                    wire:click="dismissToday"
                    type="button"
                    class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 focus:outline-none transition"
                    title="Ocultar alertas del día del indicador principal"
                >
                    <svg class="w-4 h-4 me-2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Limpiar Notificaciones del Día
                </button>

                <a
                    href="{{ route('inventory.expiration-alerts.pdf', ['urgency' => $urgencyFilter, 'search' => $search]) }}"
                    target="_blank"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium shadow-sm transition"
                >
                    <svg class="w-4 h-4 me-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Descargar Guía de Retiro (PDF)
                </a>
            </div>
        </div>

        @if($dismissMessage)
            <div class="p-4 rounded-lg bg-blue-50 border border-blue-200 text-blue-800 text-sm flex items-center justify-between shadow-sm">
                <div class="flex items-center space-x-2">
                    <svg class="w-5 h-5 text-blue-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ $dismissMessage }}</span>
                </div>
                <button type="button" wire:click="$set('dismissMessage', '')" class="text-blue-500 hover:text-blue-700 font-bold">
                    ✕
                </button>
            </div>
        @endif

        <!-- Summary Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Total Lots Card -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total en Alerta</span>
                    <span class="p-2 rounded-lg bg-slate-100 text-slate-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-bold text-gray-900">{{ $metrics['total_lots'] }}</span>
                    <span class="text-xs text-gray-500">{{ number_format($metrics['total_units'], 0, ',', '.') }} uds</span>
                </div>
            </div>

            <!-- Critical (<= 30 days) Card -->
            <div
                wire:click="setUrgencyFilter('critical')"
                class="bg-white p-4 rounded-xl shadow-sm border {{ $urgencyFilter === 'critical' || $urgencyFilter === '30' ? 'border-red-500 ring-2 ring-red-300' : 'border-red-200' }} cursor-pointer hover:shadow-md transition"
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-red-600 uppercase tracking-wider">Crítico (≤ 30 días)</span>
                    <span class="p-2 rounded-lg bg-red-100 text-red-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-bold text-red-700">{{ $metrics['critical_count'] }}</span>
                    <span class="text-xs font-semibold text-red-600">${{ number_format($metrics['critical_monetary_risk'], 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- Warning (31 to 60 days) Card -->
            <div
                wire:click="setUrgencyFilter('warning')"
                class="bg-white p-4 rounded-xl shadow-sm border {{ $urgencyFilter === 'warning' || $urgencyFilter === '60' ? 'border-orange-500 ring-2 ring-orange-300' : 'border-orange-200' }} cursor-pointer hover:shadow-md transition"
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-orange-600 uppercase tracking-wider">Advertencia (31-60d)</span>
                    <span class="p-2 rounded-lg bg-orange-100 text-orange-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-bold text-orange-700">{{ $metrics['warning_count'] }}</span>
                    <span class="text-xs font-semibold text-orange-600">${{ number_format($metrics['warning_monetary_risk'], 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- Attention (61 to 90 days) Card -->
            <div
                wire:click="setUrgencyFilter('attention')"
                class="bg-white p-4 rounded-xl shadow-sm border {{ $urgencyFilter === 'attention' || $urgencyFilter === '90' ? 'border-amber-500 ring-2 ring-amber-300' : 'border-amber-200' }} cursor-pointer hover:shadow-md transition"
            >
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold text-amber-600 uppercase tracking-wider">Atención (61-90d)</span>
                    <span class="p-2 rounded-lg bg-amber-100 text-amber-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-2xl font-bold text-amber-700">{{ $metrics['attention_count'] }}</span>
                    <span class="text-xs font-semibold text-amber-600">${{ number_format($metrics['attention_monetary_risk'], 0, ',', '.') }}</span>
                </div>
            </div>

            <!-- Monetary Risk Card -->
            <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Valor en Riesgo</span>
                    <span class="p-2 rounded-lg bg-emerald-100 text-emerald-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </span>
                </div>
                <div class="mt-2">
                    <span class="text-xl font-extrabold text-gray-900">
                        ${{ number_format($metrics['total_monetary_risk'], 0, ',', '.') }}
                    </span>
                    <div class="text-[11px] text-gray-500">Pesos Colombianos (COP)</div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-200 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <!-- Urgency Filter Tabs -->
            <div class="flex flex-wrap items-center gap-2">
                <button
                    wire:click="setUrgencyFilter('')"
                    type="button"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $urgencyFilter === '' ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' }}"
                >
                    Todos ({{ $metrics['total_lots'] }})
                </button>
                <button
                    wire:click="setUrgencyFilter('critical')"
                    type="button"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $urgencyFilter === 'critical' || $urgencyFilter === '30' ? 'bg-red-600 text-white' : 'bg-red-50 text-red-700 border border-red-200 hover:bg-red-100' }}"
                >
                    ≤ 30 Días · Rojo ({{ $metrics['critical_count'] }})
                </button>
                <button
                    wire:click="setUrgencyFilter('warning')"
                    type="button"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $urgencyFilter === 'warning' || $urgencyFilter === '60' ? 'bg-orange-600 text-white' : 'bg-orange-50 text-orange-700 border border-orange-200 hover:bg-orange-100' }}"
                >
                    31-60 Días · Naranja ({{ $metrics['warning_count'] }})
                </button>
                <button
                    wire:click="setUrgencyFilter('attention')"
                    type="button"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold transition {{ $urgencyFilter === 'attention' || $urgencyFilter === '90' ? 'bg-amber-600 text-white' : 'bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100' }}"
                >
                    61-90 Días · Amarillo ({{ $metrics['attention_count'] }})
                </button>
            </div>

            <!-- Search Input -->
            <div class="relative w-full md:w-80">
                <input
                    wire:model.live.debounce.300ms="search"
                    type="text"
                    placeholder="Buscar medicamento, lote o código..."
                    class="w-full pl-10 pr-4 py-2 rounded-lg border border-gray-300 bg-white text-gray-900 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                />
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                @if($search)
                    <button
                        wire:click="$set('search', '')"
                        type="button"
                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
                    >
                        ✕
                    </button>
                @endif
            </div>
        </div>

        <!-- Data Table Card -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Urgencia
                            </th>
                            <th scope="col" class="px-4 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Medicamento
                            </th>
                            <th scope="col" class="px-4 py-3.5 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Lote
                            </th>
                            <th scope="col" class="px-4 py-3.5 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Vencimiento
                            </th>
                            <th scope="col" class="px-4 py-3.5 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Días Restantes
                            </th>
                            <th scope="col" class="px-4 py-3.5 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Existencias
                            </th>
                            <th scope="col" class="px-4 py-3.5 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Valor en Riesgo
                            </th>
                            <th scope="col" class="px-4 py-3.5 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse($lots as $lot)
                            @php
                                $days = (int) \Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($lot->expiration_date), false);
                                $tier = $days <= 30 ? 'critical' : ($days <= 60 ? 'warning' : 'attention');
                                $risk = (int) round((float) $lot->current_quantity * (float) $lot->unit_purchase_price);
                            @endphp
                            <tr class="hover:bg-slate-50 transition">
                                <!-- Urgency Badge -->
                                <td class="px-4 py-4 whitespace-nowrap text-sm">
                                    @if($tier === 'critical')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200">
                                            <span class="w-1.5 h-1.5 me-1.5 bg-red-600 rounded-full"></span>
                                            ≤ 30d · Rojo
                                        </span>
                                    @elseif($tier === 'warning')
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-orange-100 text-orange-800 border border-orange-200">
                                            <span class="w-1.5 h-1.5 me-1.5 bg-orange-600 rounded-full"></span>
                                            31-60d · Naranja
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                            <span class="w-1.5 h-1.5 me-1.5 bg-amber-600 rounded-full"></span>
                                            61-90d · Amarillo
                                        </span>
                                    @endif
                                </td>

                                <!-- Medicine Details -->
                                <td class="px-4 py-4 text-sm">
                                    <div class="font-semibold text-gray-900">
                                        {{ $lot->medicine->name }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $lot->medicine->generic_name }} · {{ $lot->medicine->presentation }}
                                    </div>
                                    <div class="text-[11px] text-gray-400">
                                        Lab: {{ $lot->medicine->laboratory?->name ?? 'N/A' }}
                                    </div>
                                </td>

                                <!-- Batch Number -->
                                <td class="px-4 py-4 whitespace-nowrap text-sm font-mono font-medium text-gray-800">
                                    {{ $lot->batch_number }}
                                </td>

                                <!-- Expiration Date -->
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-center text-gray-700">
                                    {{ \Carbon\Carbon::parse($lot->expiration_date)->format('d/m/Y') }}
                                </td>

                                <!-- Days Remaining -->
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-center font-bold">
                                    @if($tier === 'critical')
                                        <span class="text-red-600">{{ $days }} días</span>
                                    @elseif($tier === 'warning')
                                        <span class="text-orange-600">{{ $days }} días</span>
                                    @else
                                        <span class="text-amber-600">{{ $days }} días</span>
                                    @endif
                                </td>

                                <!-- Current Quantity -->
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-900">
                                    {{ number_format($lot->current_quantity, 0, ',', '.') }}
                                </td>

                                <!-- Monetary Risk -->
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-right font-semibold text-gray-800">
                                    ${{ number_format($risk, 0, ',', '.') }}
                                </td>

                                <!-- Actions -->
                                <td class="px-4 py-4 whitespace-nowrap text-sm text-center">
                                    <a
                                        href="{{ route('inventory.lots.logs', $lot) }}"
                                        class="text-blue-600 hover:text-blue-900 text-xs font-semibold hover:underline inline-flex items-center"
                                        title="Ver historial de movimientos de este lote"
                                        wire:navigate
                                    >
                                        Ver Logs
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    <div class="flex flex-col items-center justify-center space-y-2">
                                        <svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        <p class="text-base font-medium text-gray-700">
                                            No se encontraron lotes en alerta de vencimiento
                                        </p>
                                        <p class="text-xs text-gray-400">
                                            Todos los lotes activos se encuentran seguros (> 90 días de vigencia) o no coinciden con los filtros aplicados.
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($lots->hasPages())
                <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">
                    {{ $lots->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

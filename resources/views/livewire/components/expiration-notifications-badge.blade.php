<?php

declare(strict_types=1);

use App\Services\ExpirationAlertService;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Volt\Component;

new class extends Component
{
    public int $unreadCount = 0;

    public int $totalAlertCount = 0;

    public array $previewBatches = [];

    public function mount(ExpirationAlertService $service): void
    {
        $this->loadData($service);
    }

    #[On('expiration-alerts-updated')]
    #[On('echo:inventory,LotUpdated')]
    public function refreshAlerts(ExpirationAlertService $service): void
    {
        $this->loadData($service);
    }

    public function loadData(ExpirationAlertService $service): void
    {
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $this->unreadCount = $service->getUnreadAlertCountForUser($user);
        $this->totalAlertCount = $service->getExpiringLotsQuery(90)->count();

        $batches = $service->getTopUrgentBatches(5);

        $this->previewBatches = $batches->map(function ($lot) use ($service) {
            $days = (int) Carbon::today()->diffInDays(Carbon::parse($lot->expiration_date), false);

            return [
                'id' => $lot->id,
                'medicine_name' => $lot->medicine->name,
                'generic_name' => $lot->medicine->generic_name,
                'batch_number' => $lot->batch_number,
                'expiration_date' => $lot->expiration_date,
                'days_remaining' => $days,
                'tier' => $service->getUrgencyTier($days),
                'quantity' => $lot->current_quantity,
            ];
        })->toArray();
    }

    public function dismissToday(ExpirationAlertService $service): void
    {
        $user = auth()->user();

        if ($user) {
            $service->dismissAlertsForToday($user);
            $this->loadData($service);
            $this->dispatch('expiration-alerts-updated');
        }
    }
}; ?>

<div class="relative inline-flex items-center mr-1" x-data="{ open: false }" @click.outside="open = false" @close.stop="open = false">
    <!-- Notification Bell Button -->
    <button
        @click="open = !open"
        type="button"
        class="relative p-2 text-gray-500 rounded-lg hover:text-gray-900 hover:bg-gray-100 focus:ring-4 focus:ring-gray-300 transition ease-in-out duration-150"
        title="Alertas de Vencimiento"
        id="expiration-alerts-bell-btn"
    >
        <span class="sr-only">Alertas de Vencimiento</span>
        <svg aria-hidden="true" class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
            <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z"></path>
        </svg>

        @if($unreadCount > 0)
            <span class="absolute top-1 right-1 flex h-4 w-4">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-4 w-4 bg-red-600 text-white text-[10px] font-bold items-center justify-center">
                    {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                </span>
            </span>
        @endif
    </button>

    <!-- Dropdown Menu -->
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 top-full mt-2 w-80 sm:w-96 rounded-xl shadow-xl bg-white border border-gray-200 z-50 overflow-hidden"
        style="display: none;"
    >
        <!-- Dropdown Header -->
        <div class="px-4 py-3 bg-gray-50 border-b border-gray-200 flex items-center justify-between">
            <div class="flex items-center space-x-2">
                <span class="font-bold text-sm text-gray-900">Alertas de Vencimiento</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                    {{ $totalAlertCount }} total
                </span>
            </div>

            @if($unreadCount > 0)
                <button
                    wire:click="dismissToday"
                    type="button"
                    class="text-xs text-blue-600 hover:text-blue-800 font-medium hover:underline"
                    title="Ocultar indicador del día"
                >
                    Limpiar hoy
                </button>
            @endif
        </div>

        <!-- Notification List -->
        <div class="max-h-72 overflow-y-auto divide-y divide-gray-100 bg-white">
            @forelse($previewBatches as $batch)
                <div class="p-3 hover:bg-slate-50 transition duration-150">
                    <div class="flex items-start justify-between">
                        <div class="flex-1 min-w-0 pr-2">
                            <p class="text-xs font-semibold text-gray-900 truncate">
                                {{ $batch['medicine_name'] }}
                            </p>
                            <p class="text-[11px] text-gray-600 mt-0.5">
                                Lote: <span class="font-mono font-medium text-gray-800">{{ $batch['batch_number'] }}</span> · Stock: <span class="font-bold text-gray-900">{{ $batch['quantity'] }}</span>
                            </p>
                            <p class="text-[10px] text-gray-500">
                                Vence: {{ \Carbon\Carbon::parse($batch['expiration_date'])->format('d/m/Y') }}
                            </p>
                        </div>

                        <div class="shrink-0">
                            @if($batch['tier'] === 'critical')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800 border border-red-200">
                                    {{ $batch['days_remaining'] }}d · Rojo
                                </span>
                            @elseif($batch['tier'] === 'warning')
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-orange-100 text-orange-800 border border-orange-200">
                                    {{ $batch['days_remaining'] }}d · Naranja
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                    {{ $batch['days_remaining'] }}d · Amarillo
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="p-6 text-center text-xs text-gray-500">
                    <svg class="mx-auto h-8 w-8 text-green-500 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    No hay alertas de vencimiento en los próximos 90 días.
                </div>
            @endforelse
        </div>

        <!-- Dropdown Footer -->
        <div class="p-2.5 bg-gray-50 border-t border-gray-200 text-center">
            <a
                href="{{ route('inventory.expiration-alerts') }}"
                @click="open = false"
                class="block w-full py-1.5 text-xs font-semibold text-blue-600 hover:text-blue-800 rounded hover:bg-blue-50 transition"
                wire:navigate
            >
                Ver listado detallado de alertas →
            </a>
        </div>
    </div>
</div>

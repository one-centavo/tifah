<?php

declare(strict_types=1);

use App\Services\DashboardService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public string $period = 'month'; // 'today', 'week', 'month', 'quarter', 'year'
    public string $activeAlertTab = 'expiring_lots'; // 'expiring_lots', 'low_stock', 'sanitary_registries', 'movements'
    public int $chartDays = 15;

    public function setPeriod(string $period): void
    {
        $this->period = $period;
    }

    public function setActiveAlertTab(string $tab): void
    {
        $this->activeAlertTab = $tab;
    }

    public function setChartDays(int $days): void
    {
        $this->chartDays = $days;
    }

    public function with(DashboardService $dashboardService): array
    {
        return [
            'kpis' => $dashboardService->getKpiMetrics($this->period),
            'alerts' => $dashboardService->getCriticalAlerts(),
            'fefo' => $dashboardService->getFefoDistribution(),
            'salesVsPurchasesChart' => $dashboardService->getSalesVsPurchasesChartData($this->chartDays),
            'topMedicines' => $dashboardService->getTopSellingMedicines(5, 30),
            'specialControlSummary' => $dashboardService->getSpecialControlSummary(),
            'recentMovements' => $dashboardService->getRecentInventoryMovements(8),
        ];
    }
}; ?>

<div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 space-y-6">
    <!-- Top Action & Welcome Bar -->
    <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-blue-100 text-blue-800">
                        <x-tabler-dashboard class="w-5 h-5" />
                    </span>
                    <h1 class="text-2xl font-bold text-gray-900">Panel de Control & Trazabilidad</h1>
                </div>
                <p class="text-sm text-gray-500 mt-1">
                    Supervisión operativa, control FEFO, alertas regulatorias y métricas de distribución en tiempo real.
                </p>
            </div>

            <!-- Period Filter & Quick Actions -->
            <div class="flex flex-wrap items-center gap-2.5">
                <div class="inline-flex rounded-lg shadow-xs bg-gray-100 p-1 border border-gray-200" role="group">
                    <button type="button" wire:click="setPeriod('today')"
                        class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ $period === 'today' ? 'bg-white text-blue-900 shadow-xs font-bold' : 'text-gray-600 hover:text-gray-900' }}">
                        Hoy
                    </button>
                    <button type="button" wire:click="setPeriod('week')"
                        class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ $period === 'week' ? 'bg-white text-blue-900 shadow-xs font-bold' : 'text-gray-600 hover:text-gray-900' }}">
                        7 Días
                    </button>
                    <button type="button" wire:click="setPeriod('month')"
                        class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ $period === 'month' ? 'bg-white text-blue-900 shadow-xs font-bold' : 'text-gray-600 hover:text-gray-900' }}">
                        Este Mes
                    </button>
                    <button type="button" wire:click="setPeriod('year')"
                        class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ $period === 'year' ? 'bg-white text-blue-900 shadow-xs font-bold' : 'text-gray-600 hover:text-gray-900' }}">
                        Este Año
                    </button>
                </div>

                <a href="{{ route('sales.create') }}" wire:navigate
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-blue-900 hover:bg-blue-800 rounded-lg shadow-sm transition-colors">
                    <x-tabler-shopping-cart-plus class="w-4 h-4" />
                    <span>Facturar</span>
                </a>

                <a href="{{ route('inventory.index') }}" wire:navigate
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-gray-700 bg-white hover:bg-gray-50 border border-gray-300 rounded-lg shadow-sm transition-colors">
                    <x-tabler-package-import class="w-4 h-4 text-gray-500" />
                    <span>Inventario</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Row 1: KPI Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- KPI 1: Ventas del Periodo -->
        <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Ventas del Periodo</span>
                <span class="p-2 bg-emerald-50 text-emerald-600 rounded-lg">
                    <x-tabler-currency-dollar class="w-5 h-5" />
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold text-gray-900">
                    ${{ number_format($kpis['period_sales'], 2, ',', '.') }}
                </div>
                <div class="flex items-center gap-1 mt-1 text-xs text-gray-500">
                    <span>Hoy:</span>
                    <span class="font-semibold text-emerald-700">${{ number_format($kpis['today_sales'], 2, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <!-- KPI 2: Valorización del Inventario -->
        <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Valor en Inventario</span>
                <span class="p-2 bg-blue-50 text-blue-700 rounded-lg">
                    <x-tabler-building-warehouse class="w-5 h-5" />
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold text-gray-900">
                    ${{ number_format($kpis['inventory_cost_valuation'], 2, ',', '.') }}
                </div>
                <div class="flex items-center gap-1 mt-1 text-xs text-gray-500">
                    <span>Precio Venta:</span>
                    <span class="font-semibold text-blue-900">${{ number_format($kpis['inventory_selling_valuation'], 2, ',', '.') }}</span>
                    <span class="text-emerald-700 text-xs font-medium">({{ $kpis['potential_profit_margin'] }}% mrg)</span>
                </div>
            </div>
        </div>

        <!-- KPI 3: Stock Crítico -->
        <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Stock Crítico</span>
                <span class="p-2 {{ $kpis['critical_stock_medicines_count'] > 0 ? 'bg-amber-50 text-amber-600' : 'bg-gray-50 text-gray-500' }} rounded-lg">
                    <x-tabler-alert-triangle class="w-5 h-5" />
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold {{ $kpis['critical_stock_medicines_count'] > 0 ? 'text-amber-600' : 'text-gray-900' }}">
                    {{ $kpis['critical_stock_medicines_count'] }}
                </div>
                <div class="mt-1 text-xs text-gray-500">
                    Medicamentos con stock &le; mínimo
                </div>
            </div>
        </div>

        <!-- KPI 4: Lotes en Riesgo FEFO -->
        <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Lotes por Vencer (&le;30d)</span>
                <span class="p-2 {{ $kpis['expiring_lots_count'] > 0 ? 'bg-rose-50 text-rose-600' : 'bg-gray-50 text-gray-500' }} rounded-lg">
                    <x-tabler-calendar-due class="w-5 h-5" />
                </span>
            </div>
            <div class="mt-2">
                <div class="text-2xl font-bold {{ $kpis['expiring_lots_count'] > 0 ? 'text-rose-600' : 'text-gray-900' }}">
                    {{ $kpis['expiring_lots_count'] }}
                </div>
                <div class="flex items-center justify-between mt-1 text-xs text-gray-500">
                    <span>Requiere acción inmediata</span>
                    <a href="{{ route('inventory.index') }}" wire:navigate class="text-blue-900 font-semibold hover:underline">Ver</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 2: Status & Quick Insights Banner -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
        <!-- Cold Chain & Special Control Monitor -->
        <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <div class="flex items-center gap-2">
                    <x-tabler-snowflake class="w-5 h-5 text-cyan-600" />
                    <h3 class="text-sm font-bold text-gray-900">Productos con Control Especial</h3>
                </div>
                <span class="text-xs bg-cyan-50 text-cyan-800 px-2 py-0.5 rounded-full font-medium">Auditoría</span>
            </div>
            <div class="grid grid-cols-2 gap-3 mt-3">
                <div class="bg-cyan-50/50 p-3 rounded-lg border border-cyan-100">
                    <div class="flex items-center gap-1.5 text-xs font-semibold text-cyan-900">
                        <x-tabler-snowflake class="w-4 h-4 text-cyan-700" />
                        <span>Cadena de Frío</span>
                    </div>
                    <div class="mt-1.5 flex items-baseline justify-between">
                        <span class="text-lg font-bold text-cyan-950">{{ $specialControlSummary['cold_chain_units'] }}</span>
                        <span class="text-xs text-cyan-700 font-medium">{{ $specialControlSummary['cold_chain_medicines'] }} SKUs</span>
                    </div>
                </div>

                <div class="bg-purple-50/50 p-3 rounded-lg border border-purple-100">
                    <div class="flex items-center gap-1.5 text-xs font-semibold text-purple-900">
                        <x-tabler-shield-lock class="w-4 h-4 text-purple-700" />
                        <span>Control Especial</span>
                    </div>
                    <div class="mt-1.5 flex items-baseline justify-between">
                        <span class="text-lg font-bold text-purple-950">{{ $specialControlSummary['special_control_units'] }}</span>
                        <span class="text-xs text-purple-700 font-medium">{{ $specialControlSummary['special_control_medicines'] }} SKUs</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- FEFO Semaphore Summary -->
        <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <div class="flex items-center gap-2">
                    <x-tabler-hourglass-empty class="w-5 h-5 text-indigo-600" />
                    <h3 class="text-sm font-bold text-gray-900">Matriz de Vencimiento FEFO</h3>
                </div>
                <span class="text-xs text-gray-500 font-medium">{{ $fefo['total'] }} Lotes Activos</span>
            </div>
            <div class="grid grid-cols-3 gap-2 mt-3 text-center">
                <div class="bg-rose-50 p-2.5 rounded-lg border border-rose-100">
                    <span class="block text-xs font-semibold text-rose-800">&le; 30 días</span>
                    <span class="text-lg font-extrabold text-rose-600 mt-1 block">{{ $fefo['critical'] }}</span>
                </div>
                <div class="bg-amber-50 p-2.5 rounded-lg border border-amber-100">
                    <span class="block text-xs font-semibold text-amber-800">31 - 90 días</span>
                    <span class="text-lg font-extrabold text-amber-600 mt-1 block">{{ $fefo['warning'] }}</span>
                </div>
                <div class="bg-emerald-50 p-2.5 rounded-lg border border-emerald-100">
                    <span class="block text-xs font-semibold text-emerald-800">&gt; 90 días</span>
                    <span class="text-lg font-extrabold text-emerald-600 mt-1 block">{{ $fefo['optimal'] }}</span>
                </div>
            </div>
        </div>

        <!-- Quarantine & Supply Status -->
        <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
            <div class="flex items-center justify-between pb-3 border-b border-gray-100">
                <div class="flex items-center gap-2">
                    <x-tabler-truck-loading class="w-5 h-5 text-gray-700" />
                    <h3 class="text-sm font-bold text-gray-900">Abastecimiento & Calidad</h3>
                </div>
                <span class="text-xs bg-gray-100 text-gray-700 px-2 py-0.5 rounded-full font-medium">Logística</span>
            </div>
            <div class="grid grid-cols-2 gap-3 mt-3">
                <div class="bg-gray-50 p-3 rounded-lg border border-gray-200">
                    <div class="text-xs font-semibold text-gray-700">Órdenes en Tránsito</div>
                    <div class="mt-1 flex items-baseline justify-between">
                        <span class="text-lg font-bold text-gray-900">{{ $kpis['pending_purchase_orders_count'] }}</span>
                        <span class="text-xs text-gray-500 font-medium">${{ number_format($kpis['pending_orders_amount'], 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="bg-orange-50 p-3 rounded-lg border border-orange-200">
                    <div class="text-xs font-semibold text-orange-900">En Cuarentena / Avería</div>
                    <div class="mt-1 flex items-baseline justify-between">
                        <span class="text-lg font-bold text-orange-950">{{ $alerts['quarantine_lots_count'] }}</span>
                        <span class="text-xs text-orange-700 font-medium">Lotes aislados</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 3: Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Sales & Purchases Trend Chart -->
        <div class="lg:col-span-2 bg-white border border-gray-200 rounded-xl p-5 shadow-sm min-w-0"
            x-data="{
                chartInstance: null,
                init() {
                    this.$nextTick(() => {
                        this.renderChart();
                    });
                },
                renderChart() {
                    const ctx = document.getElementById('salesTrendChart');
                    if (!ctx || !window.Chart) return;

                    if (this.chartInstance) {
                        this.chartInstance.destroy();
                    }

                    const labels = @js($salesVsPurchasesChart['labels']);
                    const salesData = @js($salesVsPurchasesChart['sales']);
                    const purchasesData = @js($salesVsPurchasesChart['purchases']);

                    this.chartInstance = new window.Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: labels,
                            datasets: [
                                {
                                    label: 'Ventas ($)',
                                    data: salesData,
                                    borderColor: '#1e3a8a',
                                    backgroundColor: 'rgba(30, 58, 138, 0.08)',
                                    fill: true,
                                    tension: 0.35,
                                    pointRadius: 3,
                                    pointBackgroundColor: '#1e3a8a',
                                    borderWidth: 2.5,
                                },
                                {
                                    label: 'Compras / Órdenes ($)',
                                    data: purchasesData,
                                    borderColor: '#059669',
                                    backgroundColor: 'rgba(5, 150, 105, 0.05)',
                                    fill: true,
                                    tension: 0.35,
                                    pointRadius: 3,
                                    pointBackgroundColor: '#059669',
                                    borderWidth: 2,
                                    borderDash: [4, 4],
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: {
                                intersect: false,
                                mode: 'index',
                            },
                            plugins: {
                                legend: {
                                    position: 'top',
                                    labels: {
                                        boxWidth: 12,
                                        font: { size: 12 }
                                    }
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return context.dataset.label + ': $' + context.parsed.y.toLocaleString('es-CO');
                                        }
                                    }
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    grid: { color: '#f3f4f6' },
                                    ticks: {
                                        callback: function(value) {
                                            return '$' + value.toLocaleString('es-CO');
                                        },
                                        font: { size: 11 }
                                    }
                                },
                                x: {
                                    grid: { display: false },
                                    ticks: { font: { size: 11 } }
                                }
                            }
                        }
                    });
                }
            }">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-base font-bold text-gray-900">Evolución Financiera (Ventas vs. Compras)</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Comparativo de flujo monetario en los últimos {{ $chartDays }} días</p>
                </div>
                <div class="inline-flex rounded-lg shadow-xs bg-gray-100 p-1 border border-gray-200">
                    <button type="button" wire:click="setChartDays(7)"
                        class="px-2.5 py-1 text-xs font-medium rounded-md transition-colors {{ $chartDays === 7 ? 'bg-white text-blue-900 shadow-xs font-bold' : 'text-gray-600 hover:text-gray-900' }}">
                        7d
                    </button>
                    <button type="button" wire:click="setChartDays(15)"
                        class="px-2.5 py-1 text-xs font-medium rounded-md transition-colors {{ $chartDays === 15 ? 'bg-white text-blue-900 shadow-xs font-bold' : 'text-gray-600 hover:text-gray-900' }}">
                        15d
                    </button>
                    <button type="button" wire:click="setChartDays(30)"
                        class="px-2.5 py-1 text-xs font-medium rounded-md transition-colors {{ $chartDays === 30 ? 'bg-white text-blue-900 shadow-xs font-bold' : 'text-gray-600 hover:text-gray-900' }}">
                        30d
                    </button>
                </div>
            </div>

            <div class="relative h-64 w-full">
                <canvas id="salesTrendChart"></canvas>
            </div>
        </div>

        <!-- FEFO Distribution Donut Chart & Top 5 Selling -->
        <div class="bg-white border border-gray-200 rounded-xl p-5 shadow-sm flex flex-col justify-between min-w-0"
            x-data="{
                chartInstance: null,
                init() {
                    this.$nextTick(() => {
                        this.renderDonut();
                    });
                },
                renderDonut() {
                    const ctx = document.getElementById('fefoDonutChart');
                    if (!ctx || !window.Chart) return;

                    if (this.chartInstance) {
                        this.chartInstance.destroy();
                    }

                    this.chartInstance = new window.Chart(ctx, {
                        type: 'doughnut',
                        data: {
                            labels: ['Crítico (&le;30d)', 'Alerta (31-90d)', 'Óptimo (&gt;90d)'],
                            datasets: [{
                                data: [{{ $fefo['critical'] }}, {{ $fefo['warning'] }}, {{ $fefo['optimal'] }}],
                                backgroundColor: ['#ef4444', '#f59e0b', '#10b981'],
                                borderWidth: 2,
                                hoverOffset: 4
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: {
                                    position: 'bottom',
                                    labels: {
                                        boxWidth: 10,
                                        font: { size: 11 }
                                    }
                                }
                            },
                            cutout: '68%'
                        }
                    });
                }
            }">
            <div>
                <h3 class="text-base font-bold text-gray-900">Estado FEFO de Lotes</h3>
                <p class="text-xs text-gray-500 mt-0.5">Distribución porcentual por proximidad a vencimiento</p>
                <div class="relative h-44 w-full mt-2">
                    <canvas id="fefoDonutChart"></canvas>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-gray-100">
                <h4 class="text-xs font-bold text-gray-700 uppercase tracking-wider mb-2">Top 5 Más Vendidos (30d)</h4>
                @if($topMedicines->isNotEmpty())
                    <ul class="divide-y divide-gray-100 text-xs">
                        @foreach($topMedicines as $med)
                            <li class="py-1.5 flex items-center justify-between">
                                <div class="truncate max-w-[160px]">
                                    <span class="font-medium text-gray-900">{{ $med->medicine_name }}</span>
                                    <span class="block text-gray-400 text-[10px] truncate">{{ $med->laboratory_name }}</span>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="font-semibold text-blue-900">{{ $med->total_units_sold }} u.</span>
                                    <span class="block text-gray-400 text-[10px]">${{ number_format((float) $med->total_revenue, 0, ',', '.') }}</span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-xs text-gray-400 text-center py-2">Sin ventas registradas en el periodo</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Row 4: Operational Data Center (Tabs) -->
    <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden min-w-0">
        <!-- Tabs Header -->
        <div class="border-b border-gray-200 bg-gray-50 px-4">
            <nav class="flex space-x-6 overflow-x-auto" aria-label="Tabs">
                <button type="button" wire:click="setActiveAlertTab('expiring_lots')"
                    class="py-3 px-1 border-b-2 font-medium text-xs whitespace-nowrap transition-colors flex items-center gap-1.5 {{ $activeAlertTab === 'expiring_lots' ? 'border-rose-600 text-rose-700 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <x-tabler-calendar-due class="w-4 h-4" />
                    <span>Lotes Próximos a Vencer</span>
                    @if(count($alerts['critical_lots']) > 0)
                        <span class="ml-1 bg-rose-100 text-rose-800 text-[10px] font-bold px-1.5 py-0.5 rounded-full">{{ count($alerts['critical_lots']) }}</span>
                    @endif
                </button>

                <button type="button" wire:click="setActiveAlertTab('low_stock')"
                    class="py-3 px-1 border-b-2 font-medium text-xs whitespace-nowrap transition-colors flex items-center gap-1.5 {{ $activeAlertTab === 'low_stock' ? 'border-amber-600 text-amber-700 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <x-tabler-alert-triangle class="w-4 h-4" />
                    <span>Medicamentos con Stock Crítico</span>
                    @if(count($alerts['low_stock_medicines']) > 0)
                        <span class="ml-1 bg-amber-100 text-amber-800 text-[10px] font-bold px-1.5 py-0.5 rounded-full">{{ count($alerts['low_stock_medicines']) }}</span>
                    @endif
                </button>

                <button type="button" wire:click="setActiveAlertTab('sanitary_registries')"
                    class="py-3 px-1 border-b-2 font-medium text-xs whitespace-nowrap transition-colors flex items-center gap-1.5 {{ $activeAlertTab === 'sanitary_registries' ? 'border-indigo-600 text-indigo-700 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <x-tabler-file-check class="w-4 h-4" />
                    <span>Registros Sanitarios por Expirar</span>
                    @if(count($alerts['critical_sanitary_registries']) > 0)
                        <span class="ml-1 bg-indigo-100 text-indigo-800 text-[10px] font-bold px-1.5 py-0.5 rounded-full">{{ count($alerts['critical_sanitary_registries']) }}</span>
                    @endif
                </button>

                <button type="button" wire:click="setActiveAlertTab('movements')"
                    class="py-3 px-1 border-b-2 font-medium text-xs whitespace-nowrap transition-colors flex items-center gap-1.5 {{ $activeAlertTab === 'movements' ? 'border-blue-900 text-blue-900 font-bold' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    <x-tabler-history class="w-4 h-4" />
                    <span>Kárdex en Vivo</span>
                </button>
            </nav>
        </div>

        <!-- Tab Content -->
        <div class="p-4">
            <!-- Tab 1: Expiring Lots -->
            @if($activeAlertTab === 'expiring_lots')
                <div class="overflow-x-auto">
                    @if($alerts['critical_lots']->isNotEmpty())
                        <table class="min-w-full divide-y divide-gray-200 text-xs">
                            <thead class="bg-gray-50 text-gray-600 uppercase font-semibold">
                                <tr>
                                    <th class="px-4 py-2.5 text-left">Medicamento</th>
                                    <th class="px-4 py-2.5 text-left">Laboratorio</th>
                                    <th class="px-4 py-2.5 text-left">N° Lote</th>
                                    <th class="px-4 py-2.5 text-center">Fecha Vencimiento</th>
                                    <th class="px-4 py-2.5 text-center">Días Restantes</th>
                                    <th class="px-4 py-2.5 text-right">Stock Disponible</th>
                                    <th class="px-4 py-2.5 text-right">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach($alerts['critical_lots'] as $lot)
                                    @php
                                        $daysRemaining = (int) \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($lot->expiration_date), false);
                                    @endphp
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2.5 font-medium text-gray-900">
                                            {{ $lot->medicine->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-4 py-2.5 text-gray-500">
                                            {{ $lot->medicine->laboratory->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-4 py-2.5 font-mono text-gray-700">
                                            {{ $lot->batch_number }}
                                        </td>
                                        <td class="px-4 py-2.5 text-center font-medium">
                                            {{ \Carbon\Carbon::parse($lot->expiration_date)->format('d/m/Y') }}
                                        </td>
                                        <td class="px-4 py-2.5 text-center">
                                            @if($daysRemaining < 0)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800">
                                                    Vencido (hace {{ abs($daysRemaining) }}d)
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-50 text-rose-700 border border-rose-200">
                                                    {{ $daysRemaining }} días
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5 text-right font-bold text-gray-900">
                                            {{ $lot->current_quantity }} u.
                                        </td>
                                        <td class="px-4 py-2.5 text-right">
                                            <a href="{{ route('inventory.medicine-lots', $lot->medicine_id) }}" wire:navigate
                                                class="text-blue-900 hover:text-blue-700 font-semibold hover:underline">
                                                Ver Lote &rarr;
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="text-center py-8 text-gray-500">
                            <x-tabler-check class="w-8 h-8 text-emerald-500 mx-auto mb-2" />
                            <p class="font-medium text-sm text-gray-800">No hay lotes en riesgo de vencimiento inminente</p>
                            <p class="text-xs text-gray-400 mt-1">Todos los lotes tienen vigencia superior a 30 días.</p>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Tab 2: Low Stock Medicines -->
            @if($activeAlertTab === 'low_stock')
                <div class="overflow-x-auto">
                    @if($alerts['low_stock_medicines']->isNotEmpty())
                        <table class="min-w-full divide-y divide-gray-200 text-xs">
                            <thead class="bg-gray-50 text-gray-600 uppercase font-semibold">
                                <tr>
                                    <th class="px-4 py-2.5 text-left">Medicamento</th>
                                    <th class="px-4 py-2.5 text-left">Laboratorio</th>
                                    <th class="px-4 py-2.5 text-left">Categoría</th>
                                    <th class="px-4 py-2.5 text-center">Stock Actual</th>
                                    <th class="px-4 py-2.5 text-center">Stock Mínimo</th>
                                    <th class="px-4 py-2.5 text-center">Déficit</th>
                                    <th class="px-4 py-2.5 text-right">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach($alerts['low_stock_medicines'] as $med)
                                    @php
                                        $currentStock = $med->active_stock ?? 0;
                                        $deficit = $med->min_stock - $currentStock;
                                    @endphp
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2.5 font-medium text-gray-900">
                                            {{ $med->name }}
                                        </td>
                                        <td class="px-4 py-2.5 text-gray-500">
                                            {{ $med->laboratory->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-4 py-2.5 text-gray-500">
                                            {{ $med->category->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-4 py-2.5 text-center font-bold {{ $currentStock == 0 ? 'text-rose-600' : 'text-amber-600' }}">
                                            {{ $currentStock }} u.
                                        </td>
                                        <td class="px-4 py-2.5 text-center text-gray-600">
                                            {{ $med->min_stock }} u.
                                        </td>
                                        <td class="px-4 py-2.5 text-center">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold {{ $currentStock == 0 ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800' }}">
                                                -{{ max(0, $deficit) }} u.
                                            </span>
                                        </td>
                                        <td class="px-4 py-2.5 text-right">
                                            <a href="{{ route('inventory.index') }}" wire:navigate
                                                class="text-blue-900 hover:text-blue-700 font-semibold hover:underline">
                                                Reabastecer &rarr;
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="text-center py-8 text-gray-500">
                            <x-tabler-check class="w-8 h-8 text-emerald-500 mx-auto mb-2" />
                            <p class="font-medium text-sm text-gray-800">Inventario con niveles óptimos de stock</p>
                            <p class="text-xs text-gray-400 mt-1">Ningún medicamento se encuentra por debajo de su umbral mínimo.</p>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Tab 3: Sanitary Registries -->
            @if($activeAlertTab === 'sanitary_registries')
                <div class="overflow-x-auto">
                    @if($alerts['critical_sanitary_registries']->isNotEmpty())
                        <table class="min-w-full divide-y divide-gray-200 text-xs">
                            <thead class="bg-gray-50 text-gray-600 uppercase font-semibold">
                                <tr>
                                    <th class="px-4 py-2.5 text-left">N° Registro Sanitario</th>
                                    <th class="px-4 py-2.5 text-left">Laboratorio</th>
                                    <th class="px-4 py-2.5 text-center">Fecha Expiración</th>
                                    <th class="px-4 py-2.5 text-center">Estado</th>
                                    <th class="px-4 py-2.5 text-right">Acción</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach($alerts['critical_sanitary_registries'] as $reg)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2.5 font-mono font-semibold text-gray-900">
                                            {{ $reg->registration_number }}
                                        </td>
                                        <td class="px-4 py-2.5 text-gray-600">
                                            {{ $reg->laboratory->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-4 py-2.5 text-center font-medium">
                                            {{ \Carbon\Carbon::parse($reg->expiration_date)->format('d/m/Y') }}
                                        </td>
                                        <td class="px-4 py-2.5 text-center">
                                            @if($reg->status === 'expired' || \Carbon\Carbon::parse($reg->expiration_date)->isPast())
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-rose-100 text-rose-800">
                                                    Vencido
                                                </span>
                                            @elseif($reg->status === 'under_renewal')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800">
                                                    En Renovación
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold bg-yellow-100 text-yellow-800">
                                                    Por Vencer
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5 text-right">
                                            <a href="{{ route('sanitary-registries.edit', $reg->id) }}" wire:navigate
                                                class="text-blue-900 hover:text-blue-700 font-semibold hover:underline">
                                                Actualizar &rarr;
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="text-center py-8 text-gray-500">
                            <x-tabler-check class="w-8 h-8 text-emerald-500 mx-auto mb-2" />
                            <p class="font-medium text-sm text-gray-800">Registros sanitarios vigentes</p>
                            <p class="text-xs text-gray-400 mt-1">Todos los registros sanitarios se encuentran activos y en orden legal.</p>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Tab 4: Kárdex Live Movements -->
            @if($activeAlertTab === 'movements')
                <div class="overflow-x-auto">
                    @if($recentMovements->isNotEmpty())
                        <table class="min-w-full divide-y divide-gray-200 text-xs">
                            <thead class="bg-gray-50 text-gray-600 uppercase font-semibold">
                                <tr>
                                    <th class="px-4 py-2.5 text-left">Fecha & Hora</th>
                                    <th class="px-4 py-2.5 text-left">Tipo</th>
                                    <th class="px-4 py-2.5 text-left">Medicamento / Lote</th>
                                    <th class="px-4 py-2.5 text-left">Concepto</th>
                                    <th class="px-4 py-2.5 text-center">Cantidad</th>
                                    <th class="px-4 py-2.5 text-center">Balance Resultante</th>
                                    <th class="px-4 py-2.5 text-right">Responsable</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @foreach($recentMovements as $mov)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-2.5 text-gray-500 whitespace-nowrap">
                                            {{ \Carbon\Carbon::parse($mov->created_at)->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="px-4 py-2.5">
                                            @if($mov->type === 'entry')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    Entrada
                                                </span>
                                            @elseif($mov->type === 'exit')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-blue-50 text-blue-700 border border-blue-200">
                                                    Salida
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                    Ajuste
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-2.5 font-medium text-gray-900">
                                            {{ $mov->lot->medicine->name ?? 'N/A' }}
                                            <span class="block text-[10px] text-gray-400 font-mono">Lote: {{ $mov->lot->batch_number ?? 'N/A' }}</span>
                                        </td>
                                        <td class="px-4 py-2.5 text-gray-600 max-w-[200px] truncate">
                                            {{ $mov->concept }}
                                        </td>
                                        <td class="px-4 py-2.5 text-center font-bold {{ $mov->type === 'entry' ? 'text-emerald-600' : ($mov->type === 'exit' ? 'text-blue-900' : 'text-amber-600') }}">
                                            {{ $mov->type === 'entry' ? '+' : ($mov->type === 'exit' ? '-' : '') }}{{ $mov->quantity }} u.
                                        </td>
                                        <td class="px-4 py-2.5 text-center font-semibold text-gray-800">
                                            {{ $mov->new_balance }} u.
                                        </td>
                                        <td class="px-4 py-2.5 text-right text-gray-500">
                                            {{ $mov->creator->name ?? 'Sistema' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="text-center py-8 text-gray-500">
                            <x-tabler-history class="w-8 h-8 text-gray-400 mx-auto mb-2" />
                            <p class="font-medium text-sm text-gray-800">Sin movimientos recientes</p>
                            <p class="text-xs text-gray-400 mt-1">Los movimientos de entrada y salida se registrarán automáticamente aquí.</p>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

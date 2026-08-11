<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\BillDetail;
use App\Models\InventoryMovement;
use App\Models\Lot;
use App\Models\Medicine;
use App\Models\PurchaseOrder;
use App\Models\SanitaryRegistry;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getKpiMetrics(string $period = 'month'): array
    {
        $now = Carbon::now();
        $startDate = match ($period) {
            'today' => $now->copy()->startOfDay(),
            'week' => $now->copy()->subDays(7)->startOfDay(),
            'quarter' => $now->copy()->subMonths(3)->startOfDay(),
            'year' => $now->copy()->startOfYear(),
            default => $now->copy()->startOfMonth(),
        };

        $periodSales = (float) Bill::query()
            ->where('status', 'active')
            ->where('created_at', '>=', $startDate)
            ->sum('total_amount');

        $todaySales = (float) Bill::query()
            ->where('status', 'active')
            ->whereDate('created_at', $now->toDateString())
            ->sum('total_amount');

        $inventoryCostValuation = (float) Lot::query()
            ->where('status', 'active')
            ->where('current_quantity', '>', 0)
            ->sum(DB::raw('current_quantity * unit_purchase_price'));

        $inventorySellingValuation = (float) DB::table('lots')
            ->join('medicines', 'lots.medicine_id', '=', 'medicines.id')
            ->whereNull('lots.deleted_at')
            ->whereNull('medicines.deleted_at')
            ->where('lots.status', 'active')
            ->where('lots.current_quantity', '>', 0)
            ->sum(DB::raw('lots.current_quantity * medicines.selling_price'));

        $criticalStockMedicinesCount = Medicine::query()
            ->withSum([
                'lots as active_stock' => function ($query) {
                    $query->where('status', 'active')->where('current_quantity', '>', 0);
                },
            ], 'current_quantity')
            ->get()
            ->filter(fn (Medicine $medicine) => ($medicine->active_stock ?? 0) <= $medicine->min_stock)
            ->count();

        $expiringLotsCount = Lot::query()
            ->where('status', 'active')
            ->where('current_quantity', '>', 0)
            ->where('expiration_date', '<=', $now->copy()->addDays(30)->toDateString())
            ->count();

        $pendingPurchaseOrders = PurchaseOrder::query()
            ->where('status', 'pending')
            ->count();

        $pendingOrdersAmount = (float) PurchaseOrder::query()
            ->where('status', 'pending')
            ->sum('total_estimated');

        return [
            'period_sales' => $periodSales,
            'today_sales' => $todaySales,
            'inventory_cost_valuation' => $inventoryCostValuation,
            'inventory_selling_valuation' => $inventorySellingValuation,
            'potential_profit_margin' => $inventoryCostValuation > 0
                ? round((($inventorySellingValuation - $inventoryCostValuation) / $inventoryCostValuation) * 100, 1)
                : 0.0,
            'critical_stock_medicines_count' => $criticalStockMedicinesCount,
            'expiring_lots_count' => $expiringLotsCount,
            'pending_purchase_orders_count' => $pendingPurchaseOrders,
            'pending_orders_amount' => $pendingOrdersAmount,
        ];
    }

    public function getCriticalAlerts(): array
    {
        $now = Carbon::now();
        $in30Days = $now->copy()->addDays(30)->toDateString();
        $in60Days = $now->copy()->addDays(60)->toDateString();

        $criticalLots = Lot::query()
            ->with(['medicine.laboratory'])
            ->where('status', 'active')
            ->where('current_quantity', '>', 0)
            ->where('expiration_date', '<=', $in30Days)
            ->orderBy('expiration_date', 'asc')
            ->limit(10)
            ->get();

        $criticalSanitaryRegistries = SanitaryRegistry::query()
            ->with(['laboratory'])
            ->where(function ($query) use ($in60Days) {
                $query->where('status', 'expired')
                    ->orWhere('expiration_date', '<=', $in60Days);
            })
            ->orderBy('expiration_date', 'asc')
            ->limit(10)
            ->get();

        $lowStockMedicines = Medicine::query()
            ->with(['laboratory', 'category'])
            ->withSum([
                'lots as active_stock' => function ($query) {
                    $query->where('status', 'active')->where('current_quantity', '>', 0);
                },
            ], 'current_quantity')
            ->get()
            ->filter(fn (Medicine $medicine) => ($medicine->active_stock ?? 0) <= $medicine->min_stock)
            ->sortBy('active_stock')
            ->values()
            ->take(10);

        $quarantineLotsCount = Lot::query()
            ->whereIn('status', ['blocked', 'damaged'])
            ->where('current_quantity', '>', 0)
            ->count();

        return [
            'critical_lots' => $criticalLots,
            'critical_sanitary_registries' => $criticalSanitaryRegistries,
            'low_stock_medicines' => $lowStockMedicines,
            'quarantine_lots_count' => $quarantineLotsCount,
        ];
    }

    public function getFefoDistribution(): array
    {
        $today = Carbon::today()->toDateString();
        $in30Days = Carbon::today()->addDays(30)->toDateString();
        $in90Days = Carbon::today()->addDays(90)->toDateString();

        $criticalCount = Lot::query()
            ->where('status', 'active')
            ->where('current_quantity', '>', 0)
            ->where('expiration_date', '<=', $in30Days)
            ->count();

        $warningCount = Lot::query()
            ->where('status', 'active')
            ->where('current_quantity', '>', 0)
            ->where('expiration_date', '>', $in30Days)
            ->where('expiration_date', '<=', $in90Days)
            ->count();

        $optimalCount = Lot::query()
            ->where('status', 'active')
            ->where('current_quantity', '>', 0)
            ->where('expiration_date', '>', $in90Days)
            ->count();

        return [
            'critical' => $criticalCount,
            'warning' => $warningCount,
            'optimal' => $optimalCount,
            'total' => $criticalCount + $warningCount + $optimalCount,
        ];
    }

    public function getSalesVsPurchasesChartData(int $days = 15): array
    {
        $startDate = Carbon::today()->subDays($days - 1)->startOfDay();

        $salesByDay = Bill::query()
            ->where('status', 'active')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, SUM(total_amount) as total')
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $purchasesByDay = PurchaseOrder::query()
            ->whereIn('status', ['received', 'pending'])
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, SUM(total_estimated) as total')
            ->groupBy('date')
            ->pluck('total', 'date')
            ->toArray();

        $labels = [];
        $salesData = [];
        $purchasesData = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $dateStr = $date->toDateString();
            $labels[] = $date->format('d M');
            $salesData[] = (float) ($salesByDay[$dateStr] ?? 0);
            $purchasesData[] = (float) ($purchasesByDay[$dateStr] ?? 0);
        }

        return [
            'labels' => $labels,
            'sales' => $salesData,
            'purchases' => $purchasesData,
        ];
    }

    public function getTopSellingMedicines(int $limit = 5, int $days = 30): Collection
    {
        $startDate = Carbon::today()->subDays($days)->startOfDay();

        return DB::table('bill_details')
            ->join('bills', 'bill_details.bill_id', '=', 'bills.id')
            ->join('lots', 'bill_details.lot_id', '=', 'lots.id')
            ->join('medicines', 'lots.medicine_id', '=', 'medicines.id')
            ->join('laboratories', 'medicines.laboratory_id', '=', 'laboratories.id')
            ->where('bills.status', 'active')
            ->where('bills.created_at', '>=', $startDate)
            ->select(
                'medicines.id',
                'medicines.name as medicine_name',
                'laboratories.name as laboratory_name',
                DB::raw('SUM(bill_details.quantity) as total_units_sold'),
                DB::raw('SUM(bill_details.subtotal) as total_revenue')
            )
            ->groupBy('medicines.id', 'medicines.name', 'laboratories.name')
            ->orderByDesc('total_units_sold')
            ->limit($limit)
            ->get();
    }

    public function getSpecialControlSummary(): array
    {
        $coldChainMedicines = Medicine::query()
            ->where('is_cold_chain', true)
            ->count();

        $coldChainUnits = (int) DB::table('lots')
            ->join('medicines', 'lots.medicine_id', '=', 'medicines.id')
            ->whereNull('lots.deleted_at')
            ->whereNull('medicines.deleted_at')
            ->where('lots.status', 'active')
            ->where('medicines.is_cold_chain', true)
            ->sum('lots.current_quantity');

        $specialControlMedicines = Medicine::query()
            ->where('is_special_control', true)
            ->count();

        $specialControlUnits = (int) DB::table('lots')
            ->join('medicines', 'lots.medicine_id', '=', 'medicines.id')
            ->whereNull('lots.deleted_at')
            ->whereNull('medicines.deleted_at')
            ->where('lots.status', 'active')
            ->where('medicines.is_special_control', true)
            ->sum('lots.current_quantity');

        return [
            'cold_chain_medicines' => $coldChainMedicines,
            'cold_chain_units' => $coldChainUnits,
            'special_control_medicines' => $specialControlMedicines,
            'special_control_units' => $specialControlUnits,
        ];
    }

    public function getRecentInventoryMovements(int $limit = 8): Collection
    {
        return InventoryMovement::query()
            ->with(['lot.medicine', 'creator'])
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();
    }
}

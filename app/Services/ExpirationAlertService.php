<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ExpirationAlertDismissal;
use App\Models\Lot;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ExpirationAlertService
{
    public const TIER_CRITICAL = 'critical'; // <= 30 days (Red)

    public const TIER_WARNING = 'warning';   // 31 to 60 days (Orange)

    public const TIER_ATTENTION = 'attention'; // 61 to 90 days (Yellow)

    /**
     * Get the base query for expiring lots within a given window.
     */
    public function getExpiringLotsQuery(
        int $maxDays = 90,
        ?string $urgencyFilter = null,
        ?string $search = null
    ): Builder {
        $today = Carbon::today()->toDateString();
        $limitDate = Carbon::today()->addDays($maxDays)->toDateString();

        $query = Lot::query()
            ->where('status', 'active')
            ->where('current_quantity', '>', 0)
            ->where('expiration_date', '>=', $today)
            ->where('expiration_date', '<=', $limitDate)
            ->with([
                'medicine.category',
                'medicine.laboratory',
                'medicine.sanitaryRegistry',
                'medicine.concentrationUnit',
                'medicine.container',
                'medicine.contentUnit',
                'purchaseOrder.supplier',
            ]);

        if ($urgencyFilter) {
            $todayCarbon = Carbon::today();
            if ($urgencyFilter === self::TIER_CRITICAL || $urgencyFilter === '30') {
                $query->whereBetween('expiration_date', [
                    $todayCarbon->toDateString(),
                    $todayCarbon->copy()->addDays(30)->toDateString(),
                ]);
            } elseif ($urgencyFilter === self::TIER_WARNING || $urgencyFilter === '60') {
                $query->whereBetween('expiration_date', [
                    $todayCarbon->copy()->addDays(31)->toDateString(),
                    $todayCarbon->copy()->addDays(60)->toDateString(),
                ]);
            } elseif ($urgencyFilter === self::TIER_ATTENTION || $urgencyFilter === '90') {
                $query->whereBetween('expiration_date', [
                    $todayCarbon->copy()->addDays(61)->toDateString(),
                    $todayCarbon->copy()->addDays(90)->toDateString(),
                ]);
            }
        }

        if ($search) {
            $searchTerm = trim($search);
            $query->where(function (Builder $q) use ($searchTerm) {
                $q->where('batch_number', 'like', "%{$searchTerm}%")
                    ->orWhereHas('medicine', function (Builder $mq) use ($searchTerm) {
                        $mq->where('name', 'like', "%{$searchTerm}%")
                            ->orWhere('generic_name', 'like', "%{$searchTerm}%")
                            ->orWhereHas('barcodes', function (Builder $bq) use ($searchTerm) {
                                $bq->where('barcode', 'like', "%{$searchTerm}%");
                            });
                    });
            });
        }

        return $query->orderBy('expiration_date', 'asc');
    }

    /**
     * Determine the urgency tier based on the remaining days until expiration.
     */
    public function getUrgencyTier(int $daysRemaining): string
    {
        if ($daysRemaining <= 30) {
            return self::TIER_CRITICAL;
        }

        if ($daysRemaining <= 60) {
            return self::TIER_WARNING;
        }

        return self::TIER_ATTENTION;
    }

    /**
     * Compute aggregated alert summary metrics and financial valuations.
     *
     * @return array<string, mixed>
     */
    public function getAlertSummaryMetrics(): array
    {
        $today = Carbon::today();
        $expiringLots = $this->getExpiringLotsQuery(90)->get();

        $totalLots = $expiringLots->count();
        $totalUnits = 0;
        $totalMonetaryRisk = 0.0;

        $criticalCount = 0;
        $criticalMonetaryRisk = 0.0;

        $warningCount = 0;
        $warningMonetaryRisk = 0.0;

        $attentionCount = 0;
        $attentionMonetaryRisk = 0.0;

        foreach ($expiringLots as $lot) {
            $days = (int) $today->diffInDays(Carbon::parse($lot->expiration_date), false);
            $tier = $this->getUrgencyTier($days);
            $monetaryRisk = (float) $lot->current_quantity * (float) $lot->unit_purchase_price;

            $totalUnits += (int) $lot->current_quantity;
            $totalMonetaryRisk += $monetaryRisk;

            if ($tier === self::TIER_CRITICAL) {
                $criticalCount++;
                $criticalMonetaryRisk += $monetaryRisk;
            } elseif ($tier === self::TIER_WARNING) {
                $warningCount++;
                $warningMonetaryRisk += $monetaryRisk;
            } else {
                $attentionCount++;
                $attentionMonetaryRisk += $monetaryRisk;
            }
        }

        return [
            'total_lots' => $totalLots,
            'total_units' => $totalUnits,
            'total_monetary_risk' => (int) round($totalMonetaryRisk),
            'critical_count' => $criticalCount,
            'critical_monetary_risk' => (int) round($criticalMonetaryRisk),
            'warning_count' => $warningCount,
            'warning_monetary_risk' => (int) round($warningMonetaryRisk),
            'attention_count' => $attentionCount,
            'attention_monetary_risk' => (int) round($attentionMonetaryRisk),
        ];
    }

    /**
     * Check if a given user has marked alerts as dismissed for today.
     */
    public function isDismissedTodayForUser(User|int $user): bool
    {
        $userId = $user instanceof User ? $user->id : $user;
        $today = Carbon::today()->toDateString();

        return ExpirationAlertDismissal::where('user_id', $userId)
            ->whereDate('dismissed_date', $today)
            ->exists();
    }

    /**
     * Get the unread alert count for the visual header badge.
     */
    public function getUnreadAlertCountForUser(User|int $user): int
    {
        if ($this->isDismissedTodayForUser($user)) {
            return 0;
        }

        return $this->getExpiringLotsQuery(90)->count();
    }

    /**
     * Mark the current day's alerts as read/dismissed for a specific user.
     */
    public function dismissAlertsForToday(User|int $user): void
    {
        $userId = $user instanceof User ? $user->id : $user;
        $today = Carbon::today()->toDateString();

        ExpirationAlertDismissal::firstOrCreate([
            'user_id' => $userId,
            'dismissed_date' => $today,
        ]);
    }

    /**
     * Get the top urgent batches for quick dropdown previews.
     */
    public function getTopUrgentBatches(int $limit = 5): Collection
    {
        return $this->getExpiringLotsQuery(90)
            ->take($limit)
            ->get();
    }

    /**
     * Prepare full dataset for the weekly administrator email report.
     *
     * @return array<string, mixed>
     */
    public function prepareWeeklyAdminReportData(): array
    {
        $metrics = $this->getAlertSummaryMetrics();
        $criticalLots = $this->getExpiringLotsQuery(30)->get();
        $warningLots = $this->getExpiringLotsQuery(60, self::TIER_WARNING)->get();
        $attentionLots = $this->getExpiringLotsQuery(90, self::TIER_ATTENTION)->get();

        return [
            'metrics' => $metrics,
            'criticalLots' => $criticalLots,
            'warningLots' => $warningLots,
            'attentionLots' => $attentionLots,
            'generatedAt' => Carbon::now(),
        ];
    }
}

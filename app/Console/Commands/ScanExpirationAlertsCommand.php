<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ExpirationAlertService;
use Illuminate\Console\Command;

class ScanExpirationAlertsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:scan-expiration-alerts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Daily scan for active inventory lots nearing expiration (30, 60, 90 days)';

    /**
     * Execute the console command.
     */
    public function handle(ExpirationAlertService $service): int
    {
        $this->info('Starting daily expiration alerts scan...');

        $metrics = $service->getAlertSummaryMetrics();

        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Expiring Batches (≤ 90d)', $metrics['total_lots']],
                ['Total Units at Risk', number_format($metrics['total_units'], 0, ',', '.')],
                ['Critical Batches (≤ 30d / Red)', $metrics['critical_count']],
                ['Warning Batches (31-60d / Orange)', $metrics['warning_count']],
                ['Attention Batches (61-90d / Yellow)', $metrics['attention_count']],
                ['Total Monetary Risk (COP)', '$'.number_format($metrics['total_monetary_risk'], 0, ',', '.')],
            ]
        );

        $this->info('Daily expiration scan completed successfully.');

        return self::SUCCESS;
    }
}

<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\WeeklyExpirationReportMail;
use App\Models\User;
use App\Services\ExpirationAlertService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class SendWeeklyExpirationReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-weekly-expiration-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send weekly consolidated expiration alerts report to all administrators';

    /**
     * Execute the console command.
     */
    public function handle(ExpirationAlertService $service): int
    {
        $this->info('Generating weekly expiration alerts report...');

        $admins = User::where('role', 'admin')->get();

        if ($admins->isEmpty()) {
            $this->warn('No active administrators found. Skipping email dispatch.');

            return self::SUCCESS;
        }

        $reportData = $service->prepareWeeklyAdminReportData();

        foreach ($admins as $admin) {
            $this->line("Dispatching report to: {$admin->email} ({$admin->name})");
            Mail::to($admin->email)->send(new WeeklyExpirationReportMail($reportData, $admin));
        }

        $this->info("Weekly expiration report sent successfully to {$admins->count()} administrator(s).");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class DispatchOverdueAlertsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'treasury:dispatch-overdue-alerts {--cooldown=4 : Minimum hours between repeated emails for the same alert} {--force : Force send ignoring cooldown}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Dispatch recurring email escalations for all RED/Overdue facilities (Fixed Deposits, Working Capital, Long Term Loans) every 4 hours until resolved.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cooldown = (int) ($this->option('cooldown') ?: 4);
        $force    = (bool) $this->option('force');

        $this->info("⏰ Checking for RED / Overdue facilities across all sub-companies (Cooldown: {$cooldown} hours)...");

        $result = NotificationService::dispatchOverdueEscalations($cooldown, $force);

        if ($result['success']) {
            $this->info("✅ " . $result['message']);
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Overdue Facilities Found', $result['overdue_count']],
                    ['Emails Dispatched This Cycle', $result['dispatched_count']],
                    ['Facilities in 4-Hour Cooldown', $result['skipped_count']],
                    ['Unique Recipients Notified', $result['recipient_count']],
                ]
            );
            return Command::SUCCESS;
        }

        $this->warn("⚠️ " . $result['message']);
        return Command::FAILURE;
    }
}
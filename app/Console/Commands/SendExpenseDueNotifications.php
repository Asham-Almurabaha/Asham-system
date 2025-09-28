<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Expenses\Services\ExpenseDueNotifier;

class SendExpenseDueNotifications extends Command
{
    protected $signature = 'expenses:notify-due {--force : Ignore last notification timestamp and resend}';

    protected $description = 'Send notifications for due or overdue expenses';

    public function handle(ExpenseDueNotifier $notifier): int
    {
        $force = (bool) $this->option('force');
        $result = $notifier->notify($force);

        if (! $result['count']) {
            $this->info('No due expenses found.');

            return self::SUCCESS;
        }

        if (! $result['dispatched']) {
            $this->warn('Due expenses found but no eligible recipients.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Dispatched notifications for %d expense(s) to %d recipient(s).', $result['count'], $result['recipients']->count()));

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Debts\Services\DebtDueNotifier;

class SendDebtDueNotifications extends Command
{
    protected $signature = 'debts:notify-due {--force : Ignore last notification timestamp and resend}';

    protected $description = 'Send notifications for due or overdue debts that remain open';

    public function handle(DebtDueNotifier $notifier): int
    {
        $force = (bool) $this->option('force');
        $result = $notifier->notify($force);

        if (! $result['count']) {
            $this->info('No due debts found.');

            return self::SUCCESS;
        }

        if (! $result['dispatched']) {
            $this->warn('Due debts found but no eligible recipients.');

            return self::SUCCESS;
        }

        $userCount = $result['user_recipients']->count();
        $emailCount = count($result['email_recipients']);

        $this->info(sprintf(
            'Dispatched notifications for %d debt(s) to %d user(s) and %d email recipient(s).',
            $result['count'],
            $userCount,
            $emailCount
        ));

        return self::SUCCESS;
    }
}

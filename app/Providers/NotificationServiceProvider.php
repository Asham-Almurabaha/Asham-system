<?php

namespace App\Providers;

use App\Models\Note;
use App\Services\Notifications\HeaderNotificationService;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Modules\Contracts\Entities\Contract;
use Modules\Contracts\Entities\ContractInstallment;
use Modules\Debts\Entities\Debt;
use Modules\Expenses\Entities\Expense;
use Modules\Expenses\Entities\ExpensePayment;
use Modules\Investors\Entities\Investor;
use Modules\Ledger\Entities\LedgerEntry;

class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $this->registerHeaderNotificationInvalidation();
    }

    private function registerHeaderNotificationInvalidation(): void
    {
        $models = [
            Contract::class,
            ContractInstallment::class,
            Debt::class,
            Expense::class,
            ExpensePayment::class,
            Investor::class,
            LedgerEntry::class,
            Note::class,
        ];

        $callback = static function (): void {
            app(HeaderNotificationService::class)->invalidate();
        };

        foreach ($models as $model) {
            Event::listen("eloquent.created: {$model}", $callback);
            Event::listen("eloquent.updated: {$model}", $callback);
            Event::listen("eloquent.deleted: {$model}", $callback);
            Event::listen("eloquent.restored: {$model}", $callback);
        }
    }
}

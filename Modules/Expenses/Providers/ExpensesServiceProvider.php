<?php

namespace Modules\Expenses\Providers;

use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;
use Modules\Expenses\Services\ExpenseDueNotifier;

class ExpensesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $modulePath = __DIR__.'/../';

        $this->loadRoutesFrom($modulePath.'Routes/web.php');
        $this->loadMigrationsFrom($modulePath.'Database/Migrations');
        $this->loadViewsFrom($modulePath.'Resources/views', 'expenses');
        $this->loadTranslationsFrom($modulePath.'Resources/lang', 'expenses');
        $this->loadJsonTranslationsFrom($modulePath.'Resources/lang');

        $this->registerSchedule();
    }

    public function register(): void
    {
        $this->app->singleton(ExpenseDueNotifier::class, fn () => new ExpenseDueNotifier());
    }

    protected function registerSchedule(): void
    {
        $this->app->afterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('expenses:notify-due')->dailyAt('08:00');
        });
    }
}

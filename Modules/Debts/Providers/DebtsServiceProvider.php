<?php

namespace Modules\Debts\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Debts\Services\DebtDueNotifier;

class DebtsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'debts');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'debts');
    }

    public function register(): void
    {
        $this->app->singleton(DebtDueNotifier::class, fn () => new DebtDueNotifier());
    }
}

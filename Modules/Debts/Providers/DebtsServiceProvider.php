<?php

namespace Modules\Debts\Providers;

use Illuminate\Support\ServiceProvider;

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
        //
    }
}

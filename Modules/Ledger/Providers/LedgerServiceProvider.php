<?php

namespace Modules\Ledger\Providers;

use Illuminate\Support\ServiceProvider;

class LedgerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'ledger');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'ledger');
    }

    public function register(): void
    {
        //
    }
}

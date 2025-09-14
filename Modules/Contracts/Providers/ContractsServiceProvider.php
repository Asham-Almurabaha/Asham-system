<?php

namespace Modules\Contracts\Providers;

use Illuminate\Support\ServiceProvider;

class ContractsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'contracts');
    }

    public function register(): void
    {
        //
    }
}

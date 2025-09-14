<?php

namespace Modules\Investors\Providers;

use Illuminate\Support\ServiceProvider;

class InvestorsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'investors');
    }

    public function register(): void
    {
        //
    }
}

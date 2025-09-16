<?php

namespace Modules\Lookups\Providers;

use Illuminate\Support\ServiceProvider;

class LookupsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'lookups');
    }

    public function register(): void
    {
        //
    }
}

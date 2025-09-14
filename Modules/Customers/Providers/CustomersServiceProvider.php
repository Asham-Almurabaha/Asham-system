<?php

namespace Modules\Customers\Providers;

use Illuminate\Support\ServiceProvider;

class CustomersServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'customers');
    }

    public function register(): void
    {
        //
    }
}

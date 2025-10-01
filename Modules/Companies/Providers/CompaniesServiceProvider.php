<?php

namespace Modules\Companies\Providers;

use Illuminate\Support\ServiceProvider;

class CompaniesServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'companies');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'companies');
    }

    public function register(): void
    {
        //
    }
}

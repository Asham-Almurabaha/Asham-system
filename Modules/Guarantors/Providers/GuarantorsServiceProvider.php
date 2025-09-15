<?php

namespace Modules\Guarantors\Providers;

use Illuminate\Support\ServiceProvider;

class GuarantorsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../Routes/web.php');
        $this->loadRoutesFrom(__DIR__.'/../Routes/api.php');
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'guarantors');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'guarantors');
    }

    public function register(): void
    {
        //
    }
}

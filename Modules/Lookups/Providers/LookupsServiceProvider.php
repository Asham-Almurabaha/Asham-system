<?php

namespace Modules\Lookups\Providers;

use Illuminate\Support\ServiceProvider;

class LookupsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $modulePath = __DIR__.'/../';

        $this->loadRoutesFrom($modulePath.'Routes/web.php');
        $this->loadMigrationsFrom($modulePath.'Database/Migrations');
        $this->loadViewsFrom($modulePath.'Resources/views', 'lookups');
        $this->loadTranslationsFrom($modulePath.'Resources/lang', 'lookups');
        $this->loadJsonTranslationsFrom($modulePath.'Resources/lang');
    }

    public function register(): void
    {
        //
    }
}

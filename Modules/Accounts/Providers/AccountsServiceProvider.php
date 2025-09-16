<?php

namespace Modules\Accounts\Providers;

use Illuminate\Support\ServiceProvider;

class AccountsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
        $this->loadViewsFrom(__DIR__.'/../Resources/views', 'accounts');
        $this->loadTranslationsFrom(__DIR__.'/../Resources/lang', 'accounts');
    }

    public function register(): void
    {
        //
    }
}

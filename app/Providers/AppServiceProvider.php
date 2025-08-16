<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\View;
use App\Models\Setting;



class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
{
    if ($this->app->environment('local') && class_exists(\Laravel\Pail\PailServiceProvider::class)) {
        $this->app->register(\Laravel\Pail\PailServiceProvider::class);
    }

    if ($this->app->environment('local') && class_exists(\Laravel\Breeze\BreezeServiceProvider::class)) {
    $this->app->register(\Laravel\Breeze\BreezeServiceProvider::class);
}
}


    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
      
        // هذا يجعل متغير $setting متاح في كل view
        View::composer('*', function ($view) {
            $setting = Setting::first();
            $view->with('setting', $setting);
    });
    }
}

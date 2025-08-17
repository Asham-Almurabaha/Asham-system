<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // شارِك $setting و $appName على كل القوالب
        View::composer('*', function ($view) {
            $setting = null;

            // تجنب مشاكل وقت المايجريشن
            if (Schema::hasTable('settings')) {
                $setting = Cache::remember('app.setting.latest', 3600, function () {
                    return Setting::query()->latest('id')->first();
                });
            }

            $appName = $setting?->name ?? config('app.name', 'لوحة التحكم');

            $view->with(compact('setting', 'appName'));
        });
    }
}

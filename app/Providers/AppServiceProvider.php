<?php

namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::before(function ($user, $ability) {
            return $user->hasRole('admin') ? true : null;
         });

        View::composer('*', function ($view) {
            $setting = Cache::remember('app.settings.row', 3600, fn () => Setting::query()->first());
            $locale  = app()->getLocale();
            $fallback = config('app.name', 'لوحة التحكم');

            // اسم التطبيق حسب اللغة
            $appName = $locale === 'ar'
                ? ($setting->name_ar ?? $setting->name ?? $fallback)
                : ($setting->name ?? $setting->name_ar ?? $fallback);

            $view->with('setting', $setting);
            $view->with('appName', $appName);
        });

        // تنظيف الكاش عند تحديث/حذف السجل
        Setting::saved(fn () => Cache::forget('app.settings.row'));
        Setting::deleted(fn () => Cache::forget('app.settings.row'));

        // في البيئات الأخرى، اربط الإعدادات بشرط وجود الجدول
        View::composer(['welcome', 'layouts.*'], function ($view) {
            $settings = null;

            try {
                if (Schema::hasTable('settings')) {
                    $settings = Setting::query()->first();
                }
            } catch (\Throwable $e) {
                $settings = null;
            }

            $view->with('settings', $settings);
        });
    }
}

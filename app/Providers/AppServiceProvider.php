<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // أثناء الاختبارات لا تربط أي استعلامات تعتمد على جداول غير مضمونة
        if (app()->environment('testing')) {
            View::composer(['*'], function ($view) {
                $view->with('settings', null);
            });
            return;
        }

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

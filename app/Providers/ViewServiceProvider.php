<?php

namespace App\Providers;

use App\Models\Setting;
use App\Services\Notifications\HeaderNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class ViewServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        $permissionResolver = function (): array {
            static $cached;

            if ($cached !== null) {
                return $cached;
            }

            $user = Auth::user();

            if (!$user) {
                return $cached = [];
            }

            $permissions = $user->getAllPermissions()->pluck('name')->all();

            $personalAccess = [
                'settings.account.*',
                'settings.account.edit',
                'settings.account.profile.update',
                'settings.account.password.update',
            ];

            return $cached = array_values(array_unique(array_merge($permissions, $personalAccess)));
        };

        $normalizePatterns = fn(array $patterns): array => array_values(array_filter(array_map(
            fn($pattern) => is_string($pattern) ? trim($pattern) : null,
            $patterns
        ), fn($pattern) => $pattern !== null && $pattern !== ''));

        $hasPermissionForPattern = function (array $permissions, string $pattern): bool {
            foreach ($permissions as $permission) {
                if (Str::is($pattern, $permission)) {
                    return true;
                }
            }

            return false;
        };

        Blade::if('routecanany', function (...$patterns) use ($permissionResolver, $normalizePatterns, $hasPermissionForPattern): bool {
            $flattened = [];

            foreach ($patterns as $pattern) {
                if (is_array($pattern)) {
                    $flattened = array_merge($flattened, $pattern);
                } elseif (is_string($pattern)) {
                    $flattened[] = $pattern;
                }
            }

            $flattened = $normalizePatterns($flattened);

            if (empty($flattened)) {
                return false;
            }

            $permissions = $permissionResolver();

            foreach ($flattened as $pattern) {
                if ($hasPermissionForPattern($permissions, $pattern)) {
                    return true;
                }
            }

            return false;
        });

        Blade::if('routecan', function (...$patterns) use ($permissionResolver, $normalizePatterns, $hasPermissionForPattern): bool {
            $flattened = [];

            foreach ($patterns as $pattern) {
                if (is_array($pattern)) {
                    $flattened = array_merge($flattened, $pattern);
                } elseif (is_string($pattern)) {
                    $flattened[] = $pattern;
                }
            }

            $flattened = $normalizePatterns($flattened);

            if (empty($flattened)) {
                return false;
            }

            $permissions = $permissionResolver();

            foreach ($flattened as $pattern) {
                if (!$hasPermissionForPattern($permissions, $pattern)) {
                    return false;
                }
            }

            return true;
        });

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

        View::composer('layouts.header', function ($view) {
            $service = app(HeaderNotificationService::class);
            $notifications = $service->forUser(Auth::user(), app()->getLocale());

            $view->with('headerNotifications', $notifications);
        });
    }
}

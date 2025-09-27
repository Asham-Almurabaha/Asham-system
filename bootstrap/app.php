<?php

use App\Http\Middleware\EnsureRoutePermission;
use App\Http\Middleware\SetLocale;
use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

$modulesPath = realpath(__DIR__.'/../Modules');

$moduleProviders = [];

if ($modulesPath !== false && is_dir($modulesPath)) {
    foreach (glob($modulesPath.'/*/Providers/*ServiceProvider.php') ?: [] as $providerFile) {
        $relative = substr($providerFile, strlen($modulesPath) + 1);
        $relative = str_replace('\\', '/', $relative);
        $relative = str_replace('.php', '', $relative);

        $moduleProviders[] = 'Modules\\'.str_replace('/', '\\', $relative);
    }

    $moduleProviders = array_values(array_unique($moduleProviders));
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withCommands([
        \App\Console\Commands\CheckTranslations::class,
        \App\Console\Commands\SendZakatDueNotifications::class,
    ])
    ->withProviders([
        AppServiceProvider::class,
        AuthServiceProvider::class,
        ...$moduleProviders,
    ])
    ->withMiddleware(function ($middleware) {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'permission.route' => EnsureRoutePermission::class,
        ]);
    })
    ->withMiddleware(function (Middleware $middleware): void {
        // ضيف SetLocale في web (append = يشتغل بعد وسطاء الويب الافتراضيين)
        $middleware->web(append: [
            SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class EnsureRoutePermission
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $route = $request->route();

        if (!$route) {
            return $next($request);
        }

        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        $middlewares = method_exists($route, 'gatherMiddleware')
            ? $route->gatherMiddleware()
            : ($route->action['middleware'] ?? []);

        $requiresAuth = collect($middlewares)
            ->filter(fn($middleware) => is_string($middleware))
            ->contains(function (string $middleware): bool {
                return $middleware === 'auth' || Str::startsWith($middleware, 'auth:');
            });

        if (!$requiresAuth) {
            return $next($request);
        }

        $autoConfig = config('permission.auto', []);
        if (!($autoConfig['enforce'] ?? true)) {
            return $next($request);
        }

        $actionPermission = $route->getAction('permission');

        if ($actionPermission === false) {
            return $next($request);
        }

        $permissions = [];

        if (is_string($actionPermission) && $actionPermission !== '') {
            $permissions[] = $actionPermission;
        } elseif (is_array($actionPermission)) {
            foreach ($actionPermission as $permission) {
                if (is_string($permission) && $permission !== '') {
                    $permissions[] = $permission;
                }
            }
        }

        $routeName = (string) $route->getName();

        if (empty($permissions) && $routeName !== '') {
            if ($this->shouldIgnore($routeName)) {
                return $next($request);
            }

            $permissions[] = $routeName;
        }

        $permissions = array_values(array_unique($permissions));

        if (empty($permissions)) {
            return $next($request);
        }

        foreach ($permissions as $permission) {
            if ($user->can($permission)) {
                return $next($request);
            }
        }

        abort(403);
    }

    protected function shouldIgnore(string $routeName): bool
    {
        $patterns = $this->normalizedIgnorePatterns();

        foreach ($patterns as $pattern) {
            if ($pattern === $routeName) {
                return true;
            }

            if (str_contains($pattern, '*') && fnmatch($pattern, $routeName)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<int, string>
     */
    protected function normalizedIgnorePatterns(): array
    {
        $patterns = config('permission.auto.ignore', []);

        if (is_string($patterns)) {
            $patterns = [$patterns];
        }

        if (!is_array($patterns)) {
            return [];
        }

        return array_values(array_filter(
            array_map(
                fn($pattern) => is_string($pattern) ? trim($pattern) : null,
                $patterns
            ),
            fn($pattern) => $pattern !== null && $pattern !== ''
        ));
    }
}

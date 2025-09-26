<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

trait SeedsRolesAndPermissions
{
    /**
     * Seed all permissions and roles defined for the project.
     *
     * @return array<int, string>
     */
    protected function seedRolesAndPermissions(): array
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = $this->resolvePermissionNames();
        $guard       = $this->guardName();

        foreach ($permissions as $name) {
            Permission::findOrCreate($name, $guard);
        }

        foreach ($this->rolePermissionMap($permissions) as $roleName => $rolePermissions) {
            $role = Role::findOrCreate($roleName, $guard);
            $role->syncPermissions($rolePermissions);
        }

        return $permissions;
    }

    /**
     * Resolve the guard name used for seeding.
     */
    protected function guardName(): string
    {
        return property_exists($this, 'guard') ? $this->guard : 'web';
    }

    /**
     * Build the full permission name list (manual + route-based).
     *
     * @return array<int, string>
     */
    protected function resolvePermissionNames(): array
    {
        $permissions = array_values(array_unique(array_merge(
            $this->manualPermissionNames(),
            $this->collectAuthenticatedRouteNames()
        )));

        sort($permissions);

        return $permissions;
    }

    /**
     * Permissions that do not map 1:1 with named routes.
     *
     * @return array<int, string>
     */
    protected function manualPermissionNames(): array
    {
        return [
            'view-dashboard',
            'view-audit-logs',
            'accounts.entries.view',
            'accounts.entries.create',
        ];
    }

    /**
     * Map of roles to the permissions they should receive.
     *
     * @param  array<int, string>  $permissions
     * @return array<string, array<int, string>>
     */
    protected function rolePermissionMap(array $permissions): array
    {
        return [
            'admin' => $permissions,
            'manager' => array_values(array_filter($permissions, function (string $permission): bool {
                if (str_ends_with($permission, '.destroy') && (
                    str_starts_with($permission, 'settings.') ||
                    str_starts_with($permission, 'transaction_types.') ||
                    str_starts_with($permission, 'transaction_statuses.') ||
                    str_starts_with($permission, 'installment_types.') ||
                    str_starts_with($permission, 'contract_statuses.') ||
                    str_starts_with($permission, 'categories.') ||
                    str_starts_with($permission, 'nationalities.') ||
                    str_starts_with($permission, 'titles.')
                )) {
                    return false;
                }

                return true;
            })),
            'accountant' => array_values(array_filter($permissions, function (string $permission): bool {
                return (
                    str_starts_with($permission, 'ledger.') ||
                    str_starts_with($permission, 'installments.') ||
                    str_starts_with($permission, 'reports.investors.') ||
                    in_array($permission, [
                        'contracts.index',
                        'contracts.show',
                        'contracts.print',
                        'contracts.closure',
                        'contracts.import.form',
                        'contracts.import',
                        'contracts.import.template',
                        'contracts.import.failures.fix',
                        'contracts.import.basic.form',
                        'contracts.import.basic',
                        'contracts.import.basic.template',
                        'contracts.import.basic.failures.fix',
                        'contracts.import.investors.form',
                        'contracts.import.investors',
                        'contracts.import.investors.template',
                        'contracts.import.investors.failures.fix',
                        'contracts.import.payments.form',
                        'contracts.import.payments',
                        'contracts.import.payments.template',
                        'contracts.import.payments.failures.fix',
                        'contracts.import.payments.skipped.export',
                        'contracts.export.data',
                        'contracts.export.form',
                        'contracts.export.basic',
                        'contracts.export.investors',
                        'contracts.export.payments',
                        'contracts.refresh-statuses',
                        'view-dashboard',
                        'view-audit-logs',
                        'accounts.entries.view',
                        'accounts.entries.create',
                    ], true)
                );
            })),
            'viewer' => array_values(array_filter($permissions, function (string $permission): bool {
                return (
                    (bool) preg_match('/\.(index|show|template|form|print|closure)$/', $permission) ||
                    in_array($permission, [
                        'view-dashboard',
                        'view-audit-logs',
                        'product-types.available',
                        'ajax.investors.liquidity',
                        'investors.cash',
                        'investors.liquidity',
                    ], true)
                );
            })),
        ];
    }

    /**
     * Collect named routes that require the auth middleware.
     *
     * @return array<int, string>
     */
    protected function collectAuthenticatedRouteNames(): array
    {
        $ignoredPrefixes = [
            'generated::',
            'ignition.',
            'telescope.',
            'horizon.',
            'nova.',
            'livewire.',
            'pulse.',
            'debugbar.',
            'sanctum.',
        ];

        return collect(Route::getRoutes())
            ->filter(function ($route) use ($ignoredPrefixes) {
                $name = $route->getName();
                if (!$name) {
                    return false;
                }

                foreach ($ignoredPrefixes as $prefix) {
                    if (str_starts_with($name, $prefix)) {
                        return false;
                    }
                }

                $middleware = $route->gatherMiddleware();

                return in_array('auth', $middleware, true);
            })
            ->map(fn($route) => (string) $route->getName())
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}

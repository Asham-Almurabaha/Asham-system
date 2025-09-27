<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
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
            $this->sidebarPermissionNames(),
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
     * Permission names that gate the sidebar navigation entries.
     *
     * @return array<int, string>
     */
    protected function sidebarPermissionNames(): array
    {
        return [
            'accounts.bank-accounts.index',
            'accounts.entries.goods.pay.index',
            'accounts.entries.goods.sales.index',
            'operating.cars.index',
            'operating.motocycles.index',
            'operating.motorcycles.index',
            'expenses.expense-types.index',
            'expenses.expenses.index',
            'expenses.cars.index',
            'expenses.motocycles.index',
            'expenses.motorcycles.index',
            'car-expenses.index',
            'motocycle-expenses.index',
            'motorcycle-expenses.index',
            'cars.expenses.index',
            'motocycles.expenses.index',
            'motorcycles.expenses.index',
            'accounts.safes.index',
            'ajax.investors.liquidity',
            'audit.logs',
            'categories.index',
            'claim_payers.index',
            'claim_statuses.index',
            'claimants.index',
            'contract-claims.apply-discount',
            'contract-claims.index',
            'contract-claims.payments.store',
            'contract-claims.reopen',
            'contract-claims.update-status',
            'contract_statuses.index',
            'contracts.closure',
            'contracts.create',
            'contracts.dashboard',
            'contracts.destroy',
            'contracts.edit',
            'contracts.export.data',
            'contracts.images.update',
            'contracts.import.basic.form',
            'contracts.import.form',
            'contracts.import.investors.form',
            'contracts.import.payments.form',
            'contracts.index',
            'contracts.investors.store',
            'contracts.paid',
            'contracts.print',
            'contracts.refresh-statuses',
            'contracts.show',
            'contracts.store',
            'contracts.update',
            'customer_statuses.index',
            'customers.create',
            'customers.dashboard',
            'customers.destroy',
            'customers.edit',
            'customers.export',
            'customers.import.form',
            'customers.index',
            'customers.show',
            'customers.store',
            'customers.update',
            'dashboard',
            'guarantor_statuses.index',
            'guarantors.create',
            'guarantors.dashboard',
            'guarantors.destroy',
            'guarantors.edit',
            'guarantors.export',
            'guarantors.import.form',
            'guarantors.index',
            'guarantors.show',
            'guarantors.store',
            'guarantors.update',
            'installment_statuses.index',
            'installment_types.index',
            'notes.index',
            'investors.cash',
            'investors.create',
            'investors.dashboard',
            'investors.destroy',
            'investors.edit',
            'investors.export',
            'investors.import.form',
            'investors.index',
            'investors.ledger.create',
            'investors.ledger.export',
            'investors.ledger.import.form',
            'investors.ledger.shortcuts',
            'investors.ledger.shortcuts.capital',
            'investors.ledger.shortcuts.liquidity_in',
            'investors.ledger.shortcuts.liquidity_out',
            'investors.ledger.shortcuts.zakat',
            'investors.liquidity',
            'investors.show',
            'investors.store',
            'investors.update',
            'debts.index',
            'ledger.create',
            'ledger.dashboard',
            'ledger.export',
            'ledger.import.form',
            'ledger.index',
            'ledger.office.shortcuts',
            'ledger.office.shortcuts.account_deposit',
            'ledger.office.shortcuts.account_withdrawal',
            'ledger.office.shortcuts.mukataba',
            'ledger.office.shortcuts.opening_balance',
            'ledger.office.shortcuts.sales_diff',
            'ledger.store',
            'nationalities.index',
            'product_types.index',
            'settings.database.index',
            'settings.database.restore',
            'settings.account.edit',
            'settings.index',
            'settings.permissions.index',
            'settings.roles.index',
            'settings.roles.permissions',
            'titles.index',
            'transaction_statuses.index',
            'transaction_types.index',
            'users.index',
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
        $isAdminOnly = fn(string $permission): bool => $this->isAdminOnlyPermission($permission);

        return [
            'admin' => $permissions,
            'manager' => array_values(array_filter($permissions, function (string $permission) use ($isAdminOnly): bool {
                if ($isAdminOnly($permission)) {
                    return false;
                }

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
            'accountant' => array_values(array_filter($permissions, function (string $permission) use ($isAdminOnly): bool {
                if ($isAdminOnly($permission)) {
                    return false;
                }

                if (str_starts_with($permission, 'contract-claims.') || str_starts_with($permission, 'expenses.')) {
                    return false;
                }

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
                        'accounts.entries.view',
                        'accounts.entries.create',
                    ], true)
                );
            })),
            'viewer' => array_values(array_filter($permissions, function (string $permission) use ($isAdminOnly): bool {
                if ($isAdminOnly($permission)) {
                    return false;
                }

                return (
                    (bool) preg_match('/\.(index|show|template|form|print|closure)$/', $permission) ||
                    in_array($permission, [
                        'view-dashboard',
                        'product-types.available',
                        'ajax.investors.liquidity',
                        'investors.cash',
                        'investors.liquidity',
                        'settings.database.export',
                    ], true)
                );
            })),
        ];
    }

    /**
     * Determine if the permission should be limited to admin users.
     */
    protected function isAdminOnlyPermission(string $permission): bool
    {
        if (in_array($permission, $this->settingsPermissionsAvailableToAll(), true)) {
            return false;
        }

        if (in_array($permission, $this->adminOnlyPermissionNames(), true)) {
            return true;
        }

        foreach ($this->adminOnlyPermissionPrefixes() as $prefix) {
            if (str_starts_with($permission, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Permissions that should be exclusive to admin users.
     *
     * @return array<int, string>
     */
    protected function adminOnlyPermissionNames(): array
    {
        return [
            'view-audit-logs',
            'installments.cancel_payment',
            'settings.database.import',
            'settings.database.restore',
        ];
    }

    /**
     * Permission prefixes that should be exclusive to admin users.
     *
     * @return array<int, string>
     */
    protected function adminOnlyPermissionPrefixes(): array
    {
        return [
            'audit.logs',
            'settings.',
            'nationalities.',
            'titles.',
            'customer_statuses.',
            'guarantor_statuses.',
            'contract_statuses.',
            'claim_statuses.',
            'claimants.',
            'claim_payers.',
            'installment_statuses.',
            'installment_types.',
            'transaction_statuses.',
            'transaction_types.',
            'categories.',
            'accounts.bank-accounts.',
            'accounts.safes.',
            'product_types.',
            'products.',
            'settings.roles.',
            'settings.permissions.',
            'users.',
            'customers.import',
            'customers.export',
            'guarantors.import',
            'guarantors.export',
            'investors.import',
            'investors.export',
            'investors.ledger.import',
            'investors.ledger.export',
            'contracts.import',
            'contracts.export',
            'ledger.import',
            'ledger.export',
        ];
    }

    /**
     * Settings permissions that should remain available to all authenticated users.
     *
     * @return array<int, string>
     */
    protected function settingsPermissionsAvailableToAll(): array
    {
        return [
            'settings.database.index',
            'settings.database.export',
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

        $ignoredPatterns = $this->normalizedIgnoredPermissionPatterns();

        return collect(Route::getRoutes())
            ->filter(function ($route) use ($ignoredPrefixes, $ignoredPatterns) {
                $name = $route->getName();
                if (!$name) {
                    return false;
                }

                foreach ($ignoredPrefixes as $prefix) {
                    if (str_starts_with($name, $prefix)) {
                        return false;
                    }
                }

                if ($this->shouldIgnorePermissionName($name, $ignoredPatterns)) {
                    return false;
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

    /**
     * @return array<int, string>
     */
    protected function normalizedIgnoredPermissionPatterns(): array
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

    /**
     * @param  array<int, string>  $patterns
     */
    protected function shouldIgnorePermissionName(string $name, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if ($pattern === $name) {
                return true;
            }

            if (Str::contains($pattern, '*') && fnmatch($pattern, $name)) {
                return true;
            }
        }

        return false;
    }
}

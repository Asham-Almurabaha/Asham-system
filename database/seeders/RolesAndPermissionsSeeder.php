<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    protected string $guard = 'web';

    public function run(): void
    {
        // ===== 1) كل الصلاحيات اللي هنستخدمها =====
        // - الصلاحيات اليدوية (زي view-dashboard)
        // - كل أسماء الروت المحمية بـ auth
        $manualPermissions = [
            'view-dashboard',
            'view-audit-logs',
        ];

        $routePermissions = $this->collectAuthenticatedRouteNames();

        $permissions = array_values(array_unique(array_merge($manualPermissions, $routePermissions)));
        sort($permissions);

        // أنشئ/حدّث كل Permission
        foreach ($permissions as $name) {
            Permission::findOrCreate($name, $this->guard);
        }

        // ===== 2) أدوار افتراضية =====
        $roles = [
            'admin'   => $permissions, // كل الصلاحيات
            'manager' => array_values(array_filter($permissions, function ($p) {
                // مدير: كل شيء ما عدا destroy على الإعدادات الحرّاجة
                if (str_ends_with($p, '.destroy') && (
                        str_starts_with($p, 'settings.')
                        || str_starts_with($p, 'transaction_types.')
                        || str_starts_with($p, 'transaction_statuses.')
                        || str_starts_with($p, 'installment_types.')
                        || str_starts_with($p, 'contract_statuses.')
                        || str_starts_with($p, 'categories.')
                        || str_starts_with($p, 'nationalities.')
                        || str_starts_with($p, 'titles.')
                )) return false;
                return true;
            })),
            'accountant' => array_values(array_filter($permissions, function ($p) {
                // محاسب: ليدجر + أقساط + تقارير المستثمر + مشاهدة/طباعة العقود + استيراد القيود
                return (
                    str_starts_with($p, 'ledger.')
                    || str_starts_with($p, 'installments.')
                    || str_starts_with($p, 'reports.investors.')
                    || in_array($p, ['contracts.index','contracts.show','contracts.print','contracts.closure','contracts.import.form','contracts.import','contracts.import.template','contracts.import.failures.fix'])
                    || in_array($p, ['view-dashboard','view-audit-logs'])
                );
            })),
            'viewer' => array_values(array_filter($permissions, function ($p) {
                // Viewer: عرض فقط (index/show/template/print/closure/form)
                return preg_match('/\.(index|show|template|form|print|closure)$/', $p)
                    || in_array($p, ['view-dashboard','view-audit-logs','product-types.available','ajax.investors.liquidity','investors.cash','investors.liquidity']);
            })),
        ];

        foreach ($roles as $roleName => $perms) {
            $role = Role::findOrCreate($roleName, $this->guard);
            $role->syncPermissions($perms);
        }

        // (اختياري) عيّن admin لأول مستخدم موجود
        $firstUserModel = config('auth.providers.users.model');
        /** @var \Illuminate\Database\Eloquent\Model|\Spatie\Permission\Traits\HasRoles|null $first */
        $first = $firstUserModel::query()->orderBy('id')->first();
        if ($first && method_exists($first, 'assignRole')) {
            $first->assignRole('admin');
        }
    }

    /**
     * رجّع كل أسماء الروت المحمية بـ auth (باستثناء النظامية)
     * علشان نولّد منها صلاحيات بشكل تلقائي.
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

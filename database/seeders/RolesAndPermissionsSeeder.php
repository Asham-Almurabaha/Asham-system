<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    protected string $guard = 'web';

    public function run(): void
    {
        // ===== 1) كل الصلاحيات اللي هنستخدمها =====
        // تقدر تزود/تنقص براحتك لاحقًا
        $permissions = [
            // عامة
            'view-dashboard',
            'view-audit-logs',

            // Settings (resources)
            'settings.index','settings.create','settings.store','settings.show','settings.edit','settings.update','settings.destroy',
            'nationalities.index','nationalities.create','nationalities.store','nationalities.show','nationalities.edit','nationalities.update','nationalities.destroy',
            'titles.index','titles.create','titles.store','titles.show','titles.edit','titles.update','titles.destroy',
            'contract_statuses.index','contract_statuses.create','contract_statuses.store','contract_statuses.show','contract_statuses.edit','contract_statuses.update','contract_statuses.destroy',
            'installment_statuses.index','installment_statuses.create','installment_statuses.store','installment_statuses.show','installment_statuses.edit','installment_statuses.update','installment_statuses.destroy',
            'installment_types.index','installment_types.create','installment_types.store','installment_types.show','installment_types.edit','installment_types.update','installment_types.destroy',
            'transaction_types.index','transaction_types.create','transaction_types.store','transaction_types.show','transaction_types.edit','transaction_types.update','transaction_types.destroy',
            'transaction_statuses.index','transaction_statuses.create','transaction_statuses.store','transaction_statuses.show','transaction_statuses.edit','transaction_statuses.update','transaction_statuses.destroy',
            'categories.index','categories.create','categories.store','categories.show','categories.edit','categories.update','categories.destroy',

            // Customers
            'customers.index','customers.create','customers.store','customers.show','customers.edit','customers.update','customers.destroy',
            // Customers Import
            'customers.import.form','customers.import','customers.import.template','customers.import.failures.fix',

            // Guarantors
            'guarantors.index','guarantors.create','guarantors.store','guarantors.show','guarantors.edit','guarantors.update','guarantors.destroy',
            // Guarantors Import
            'guarantors.import.form','guarantors.import','guarantors.import.template','guarantors.import.failures.fix',

            // Investors
            'investors.index','investors.create','investors.store','investors.show','investors.edit','investors.update','investors.destroy',
            // Investors Import
            'investors.import.form','investors.import','investors.import.template','investors.import.failures.fix',
            // Investor Transactions (resource)
            'investor-transactions.index','investor-transactions.create','investor-transactions.store','investor-transactions.show','investor-transactions.edit','investor-transactions.update','investor-transactions.destroy',
            // Investor Reports
            'investors.statement.statement','investors.withdrawals.withdrawals','investors.deposits.deposits','investors.transactions.transactions',
            'reports.investors.Allliquidity',
            // AJAX (لو عايز تتحكم فيها)
            'ajax.investors.liquidity','investors.cash','investors.liquidity',

            // Contracts
            'contracts.index','contracts.create','contracts.store','contracts.show','contracts.edit','contracts.update','contracts.destroy',
            'contracts.print','contracts.closure','contracts.investors.store',
            // Contracts Import
            'contracts.import.form','contracts.import','contracts.import.template','contracts.import.failures.fix',

            // Ledger
            'ledger.index','ledger.create','ledger.store',
            'ledger.transfer.create','ledger.transfer.store',
            'ledger.split.create','ledger.split.store',
            // Ledger Import
            'ledger.import.form','ledger.import','ledger.import.template','ledger.import.failures.fix',
            // AJAX Accounts
            'ajax.accounts.availability','ajax.accounts.availability.bulk',

            // Installments actions
            'installments.pay','installments.early_settle','installments.defer','installments.excuse',

            // Product types AJAX
            'product-types.available',
        ];

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
}

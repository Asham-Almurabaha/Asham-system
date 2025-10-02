<?php

return [
    'groups' => [
        [
            'key'   => 'dashboard',
            'label' => 'sidebar.Dashboard',
            'items' => [
                [
                    'key'                     => 'dashboard.home',
                    'label'                   => 'sidebar.Dashboard',
                    'permission'              => 'dashboard',
                    'additional_permissions'  => ['view-dashboard'],
                ],
            ],
        ],

        [
            'key'   => 'customers',
            'label' => 'sidebar.Customers',
            'items' => [
                [
                    'key'        => 'customers.dashboard',
                    'label'      => 'sidebar.Customers Dashboard',
                    'permission' => 'customers.dashboard',
                ],
                [
                    'key'                    => 'customers.manage',
                    'label'                  => 'sidebar.Manage Customers',
                    'permission'             => 'customers.index',
                    'additional_permissions' => [
                        'customers.create',
                        'customers.store',
                        'customers.show',
                        'customers.edit',
                        'customers.update',
                        'customers.destroy',
                        'customers.import.form',
                        'customers.import',
                        'customers.import.template',
                        'customers.import.failures.fix',
                        'customers.import.pending.confirm',
                        'customers.import.pending.ignore',
                        'customers.import.pending.store-new',
                    ],
                ],
            ],
        ],

        [
            'key'   => 'guarantors',
            'label' => 'sidebar.Guarantors',
            'items' => [
                [
                    'key'        => 'guarantors.dashboard',
                    'label'      => 'sidebar.Guarantors Dashboard',
                    'permission' => 'guarantors.dashboard',
                ],
                [
                    'key'                    => 'guarantors.manage',
                    'label'                  => 'sidebar.Manage Guarantors',
                    'permission'             => 'guarantors.index',
                    'additional_permissions' => [
                        'guarantors.create',
                        'guarantors.store',
                        'guarantors.show',
                        'guarantors.edit',
                        'guarantors.update',
                        'guarantors.destroy',
                        'guarantors.import.form',
                        'guarantors.import',
                        'guarantors.import.template',
                        'guarantors.import.failures.fix',
                        'guarantors.import.pending.confirm',
                        'guarantors.import.pending.ignore',
                        'guarantors.import.pending.store-new',
                    ],
                ],
            ],
        ],

        [
            'key'   => 'investors',
            'label' => 'sidebar.Investors',
            'items' => [
                [
                    'key'        => 'investors.dashboard',
                    'label'      => 'sidebar.Investors Dashboard',
                    'permission' => 'investors.dashboard',
                ],
                [
                    'key'                    => 'investors.manage',
                    'label'                  => 'sidebar.Manage Investors',
                    'permission'             => 'investors.index',
                    'additional_permissions' => [
                        'investors.create',
                        'investors.store',
                        'investors.show',
                        'investors.edit',
                        'investors.update',
                        'investors.destroy',
                        'investors.import.form',
                        'investors.import',
                        'investors.import.template',
                        'investors.import.failures.fix',
                        'investors.import.pending.confirm',
                        'investors.import.pending.ignore',
                        'investors.import.pending.store-new',
                        'investors.cash',
                        'investors.liquidity',
                        'investors.statement.statement',
                        'investors.withdrawals.withdrawals',
                        'investors.withdrawals.ledger',
                        'investors.withdrawals.add-contract',
                        'investors.deposits.deposits',
                        'investors.deposits.ledger',
                        'investors.deposits.installments',
                        'investors.transactions.transactions',
                        'reports.investors.outstanding',
                        'reports.investors.deposits-withdrawals',
                        'reports.investors.Allliquidity',
                        'ajax.investors.liquidity',
                        'investor-transactions.index',
                        'investor-transactions.create',
                        'investor-transactions.store',
                        'investor-transactions.show',
                        'investor-transactions.edit',
                        'investor-transactions.update',
                        'investor-transactions.destroy',
                    ],
                ],
            ],
        ],

        [
            'key'   => 'contracts',
            'label' => 'sidebar.Contracts',
            'items' => [
                [
                    'key'        => 'contracts.dashboard',
                    'label'      => 'sidebar.Contracts Dashboard',
                    'permission' => 'contracts.dashboard',
                ],
                [
                    'key'                    => 'contracts.manage',
                    'label'                  => 'sidebar.Manage Contracts',
                    'permission'             => 'contracts.index',
                    'additional_permissions' => [
                        'contracts.create',
                        'contracts.store',
                        'contracts.show',
                        'contracts.edit',
                        'contracts.update',
                        'contracts.destroy',
                        'contracts.images.update',
                        'contracts.refresh-statuses',
                        'contracts.import.form',
                        'contracts.import.basic.form',
                        'contracts.import.investors.form',
                        'contracts.import.payments.form',
                        'contracts.print',
                        'contracts.closure',
                        'contracts.paid',
                        'contracts.investors.store',
                        'contracts.export.data',
                        'contracts.export.form',
                        'contracts.export.basic',
                        'contracts.export.investors',
                        'contracts.export.payments',
                        'installments.index',
                        'installments.show',
                        'installments.edit',
                        'installments.update',
                    ],
                ],
            ],
        ],

        [
            'key'   => 'accounts',
            'label' => 'sidebar.Accounts',
            'items' => [
                [
                    'key'                    => 'accounts.dashboard',
                    'label'                  => 'sidebar.Accounts Dashboard',
                    'permission'             => 'ledger.dashboard',
                    'additional_permissions' => ['ledger.dashboard'],
                ],
                [
                    'key'                    => 'accounts.ledger',
                    'label'                  => 'sidebar.Ledger',
                    'permission'             => 'ledger.index',
                    'additional_permissions' => [
                        'ledger.create',
                        'ledger.store',
                        'ledger.transfer.create',
                        'ledger.transfer.store',
                        'ledger.split.create',
                        'ledger.split.store',
                        'ledger.export',
                    ],
                ],
                [
                    'key'        => 'accounts.transfer',
                    'label'      => 'sidebar.Internal Transfer',
                    'permission' => 'ledger.transfer.create',
                    'additional_permissions' => ['ledger.transfer.store'],
                ],
                [
                    'key'        => 'accounts.mukataba',
                    'label'      => 'sidebar.Office Mukataba Entry',
                    'permission' => 'ledger.office.shortcuts.mukataba',
                ],
                [
                    'key'        => 'accounts.sales_diff',
                    'label'      => 'sidebar.Office Sales Difference Entry',
                    'permission' => 'ledger.office.shortcuts.sales_diff',
                ],
                [
                    'key'        => 'accounts.account_deposit',
                    'label'      => 'sidebar.Office Accounts Deposit',
                    'permission' => 'ledger.office.shortcuts.account_deposit',
                ],
                [
                    'key'        => 'accounts.account_withdrawal',
                    'label'      => 'sidebar.Office Accounts Withdrawal',
                    'permission' => 'ledger.office.shortcuts.account_withdrawal',
                ],
                [
                    'key'        => 'accounts.opening_balance',
                    'label'      => 'sidebar.Office Opening Balance',
                    'permission' => 'ledger.office.shortcuts.opening_balance',
                ],
                [
                    'key'        => 'accounts.investor_capital',
                    'label'      => 'sidebar.Investor Capital Entry',
                    'permission' => 'investors.ledger.shortcuts.capital',
                ],
                [
                    'key'        => 'accounts.investor_liquidity_in',
                    'label'      => 'sidebar.Investor Liquidity Deposit',
                    'permission' => 'investors.ledger.shortcuts.liquidity_in',
                ],
                [
                    'key'        => 'accounts.investor_liquidity_out',
                    'label'      => 'sidebar.Investor Liquidity Withdrawal',
                    'permission' => 'investors.ledger.shortcuts.liquidity_out',
                ],
                [
                    'key'        => 'accounts.investor_zakat',
                    'label'      => 'sidebar.Investor Zakat Withdrawal',
                    'permission' => 'investors.ledger.shortcuts.zakat',
                ],
                [
                    'key'        => 'accounts.company_expenses',
                    'label'      => 'sidebar.Company Expenses',
                    'permission' => 'company-transactions.expenses.create',
                    'additional_permissions' => ['company-transactions.expenses.index', 'company-transactions.expenses.store'],
                ],
                [
                    'key'        => 'accounts.company_expense_payments',
                    'label'      => 'sidebar.Company Expense Payments',
                    'permission' => 'company-transactions.expenses.payments.create',
                    'additional_permissions' => ['company-transactions.expenses.payments.index', 'company-transactions.expenses.payments.store'],
                ],
                [
                    'key'        => 'accounts.goods_purchase',
                    'label'      => 'sidebar.Goods Purchase',
                    'permission' => 'accounts.entries.goods.pay.index',
                    'additional_permissions' => ['accounts.entries.goods.pay.store', 'accounts.entries.goods.pay.store-partial'],
                ],
                [
                    'key'        => 'accounts.goods_sale',
                    'label'      => 'sidebar.Goods Sale',
                    'permission' => 'accounts.entries.goods.sales.index',
                    'additional_permissions' => ['accounts.entries.goods.sales.store', 'accounts.entries.goods.sales.store-partial'],
                ],
            ],
        ],

        [
            'key'   => 'companies',
            'label' => 'sidebar.Companies',
            'items' => [
                [
                    'key'        => 'companies.dashboard',
                    'label'      => 'sidebar.Companies Dashboard',
                    'permission' => 'companies.dashboard',
                ],
                [
                    'key'                    => 'companies.transactions',
                    'label'                  => 'sidebar.Company Transactions',
                    'permission'             => 'company-transactions.index',
                    'additional_permissions' => [
                        'company-transactions.create',
                        'company-transactions.store',
                        'company-transactions.show',
                        'company-transactions.edit',
                        'company-transactions.update',
                        'company-transactions.destroy',
                    ],
                ],
            ],
        ],

        [
            'key'   => 'debts',
            'label' => 'sidebar.Debts',
            'items' => [
                [
                    'key'        => 'debts.index',
                    'label'      => 'sidebar.Debts',
                    'permission' => 'debts.index',
                ],
            ],
        ],

        [
            'key'   => 'expenses',
            'label' => 'sidebar.Expenses',
            'items' => [
                [
                    'key'        => 'expenses.overview',
                    'label'      => 'sidebar.Expenses',
                    'permission' => 'expenses.expenses.index',
                ],
                [
                    'key'        => 'expenses.operating_cars',
                    'label'      => 'sidebar.Cars',
                    'permission' => 'operating.cars.index',
                ],
                [
                    'key'        => 'expenses.operating_motorcycles',
                    'label'      => 'sidebar.Motocycles',
                    'permission' => 'operating.motocycles.index',
                    'additional_permissions' => ['operating.motorcycles.index'],
                ],
                [
                    'key'        => 'expenses.expense_cars',
                    'label'      => 'sidebar.Cars',
                    'permission' => 'expenses.cars.index',
                    'additional_permissions' => ['car-expenses.index', 'cars.expenses.index'],
                ],
                [
                    'key'        => 'expenses.expense_motorcycles',
                    'label'      => 'sidebar.Motocycles',
                    'permission' => 'expenses.motocycles.index',
                    'additional_permissions' => [
                        'expenses.motorcycles.index',
                        'motocycle-expenses.index',
                        'motorcycle-expenses.index',
                        'motocycles.expenses.index',
                        'motorcycles.expenses.index',
                    ],
                ],
            ],
        ],

        [
            'key'   => 'claims',
            'label' => 'sidebar.Claims',
            'items' => [
                [
                    'key'                    => 'claims.index',
                    'label'                  => 'sidebar.Claims',
                    'permission'             => 'contract-claims.index',
                    'additional_permissions' => [
                        'contract-claims.update-status',
                        'contract-claims.apply-discount',
                        'contract-claims.reopen',
                        'contract-claims.payments.store',
                    ],
                ],
            ],
        ],

        [
            'key'   => 'notes',
            'label' => 'sidebar.Notes',
            'items' => [
                [
                    'key'        => 'notes.index',
                    'label'      => 'sidebar.Notes',
                    'permission' => 'notes.index',
                ],
            ],
        ],

        [
            'key'   => 'data_imports',
            'label' => 'sidebar.Data Imports',
            'items' => [
                [
                    'key'                    => 'import.customers',
                    'label'                  => 'sidebar.Import Customers',
                    'permission'             => 'customers.import.form',
                    'additional_permissions' => [
                        'customers.import',
                        'customers.import.template',
                        'customers.import.failures.fix',
                        'customers.import.pending.confirm',
                        'customers.import.pending.ignore',
                        'customers.import.pending.store-new',
                    ],
                ],
                [
                    'key'                    => 'import.guarantors',
                    'label'                  => 'sidebar.Import Guarantors',
                    'permission'             => 'guarantors.import.form',
                    'additional_permissions' => [
                        'guarantors.import',
                        'guarantors.import.template',
                        'guarantors.import.failures.fix',
                        'guarantors.import.pending.confirm',
                        'guarantors.import.pending.ignore',
                        'guarantors.import.pending.store-new',
                    ],
                ],
                [
                    'key'                    => 'import.investors',
                    'label'                  => 'sidebar.Import Investors',
                    'permission'             => 'investors.import.form',
                    'additional_permissions' => [
                        'investors.import',
                        'investors.import.template',
                        'investors.import.failures.fix',
                        'investors.import.pending.confirm',
                        'investors.import.pending.ignore',
                        'investors.import.pending.store-new',
                    ],
                ],
                [
                    'key'                    => 'import.investor_ledger',
                    'label'                  => 'sidebar.Import Investor Ledger',
                    'permission'             => 'investors.ledger.import.form',
                    'additional_permissions' => [
                        'investors.ledger.import',
                        'investors.ledger.import.template',
                        'investors.ledger.import.failures.fix',
                    ],
                ],
                [
                    'key'        => 'import.contracts',
                    'label'      => 'sidebar.Import Contracts',
                    'permission' => 'contracts.import.form',
                ],
                [
                    'key'                    => 'import.contracts_basic',
                    'label'                  => 'sidebar.Import Basic Contracts',
                    'permission'             => 'contracts.import.basic.form',
                    'additional_permissions' => ['contracts.import.basic', 'contracts.import.basic.template', 'contracts.import.basic.failures.fix'],
                ],
                [
                    'key'                    => 'import.contracts_investors',
                    'label'                  => 'sidebar.Import Contract Investors',
                    'permission'             => 'contracts.import.investors.form',
                    'additional_permissions' => ['contracts.import.investors', 'contracts.import.investors.template', 'contracts.import.investors.failures.fix'],
                ],
                [
                    'key'                    => 'import.contracts_payments',
                    'label'                  => 'sidebar.Import Contract Payments',
                    'permission'             => 'contracts.import.payments.form',
                    'additional_permissions' => [
                        'contracts.import.payments',
                        'contracts.import.payments.template',
                        'contracts.import.payments.failures.fix',
                        'contracts.import.payments.skipped.export',
                    ],
                ],
                [
                    'key'                    => 'import.ledger',
                    'label'                  => 'sidebar.Import Ledger Entries',
                    'permission'             => 'ledger.import.form',
                    'additional_permissions' => ['ledger.import', 'ledger.import.template', 'ledger.import.failures.fix'],
                ],
            ],
        ],

        [
            'key'   => 'data_exports',
            'label' => 'sidebar.Data Exports',
            'items' => [
                [
                    'key'        => 'export.customers',
                    'label'      => 'sidebar.Export Customers',
                    'permission' => 'customers.export',
                ],
                [
                    'key'        => 'export.guarantors',
                    'label'      => 'sidebar.Export Guarantors',
                    'permission' => 'guarantors.export',
                ],
                [
                    'key'        => 'export.investors',
                    'label'      => 'sidebar.Export Investors',
                    'permission' => 'investors.export',
                ],
                [
                    'key'        => 'export.investor_ledger',
                    'label'      => 'sidebar.Export Investor Ledger',
                    'permission' => 'investors.ledger.export',
                ],
                [
                    'key'        => 'export.ledger',
                    'label'      => 'sidebar.Export Ledger Entries',
                    'permission' => 'ledger.export',
                ],
                [
                    'key'                    => 'export.contracts',
                    'label'                  => 'sidebar.Export Contracts',
                    'permission'             => 'contracts.export.data',
                    'additional_permissions' => [
                        'contracts.export.form',
                        'contracts.export.basic',
                        'contracts.export.investors',
                        'contracts.export.payments',
                    ],
                ],
            ],
        ],

        [
            'key'   => 'settings',
            'label' => 'sidebar.Settings',
            'items' => [
                [
                    'key'        => 'settings.general',
                    'label'      => 'sidebar.General Setting',
                    'permission' => 'settings.index',
                ],
                [
                    'key'        => 'settings.database_backup',
                    'label'      => 'sidebar.Database Backup',
                    'permission' => 'settings.database.index',
                ],
                [
                    'key'        => 'settings.database_restore',
                    'label'      => 'sidebar.Database Restore',
                    'permission' => 'settings.database.restore',
                ],
                [
                    'key'        => 'settings.nationalities',
                    'label'      => 'lookups::sidebar.Nationalities',
                    'permission' => 'nationalities.index',
                ],
                [
                    'key'        => 'settings.titles',
                    'label'      => 'lookups::sidebar.Titles',
                    'permission' => 'titles.index',
                ],
                [
                    'key'        => 'settings.customer_statuses',
                    'label'      => 'lookups::sidebar.Customer Statuses',
                    'permission' => 'customer_statuses.index',
                ],
                [
                    'key'        => 'settings.guarantor_statuses',
                    'label'      => 'lookups::sidebar.Guarantor Statuses',
                    'permission' => 'guarantor_statuses.index',
                ],
                [
                    'key'        => 'settings.contract_statuses',
                    'label'      => 'lookups::sidebar.Contract Statuses',
                    'permission' => 'contract_statuses.index',
                ],
                [
                    'key'        => 'settings.claim_statuses',
                    'label'      => 'lookups::sidebar.Claim Statuses',
                    'permission' => 'claim_statuses.index',
                ],
                [
                    'key'        => 'settings.claimants',
                    'label'      => 'lookups::sidebar.Claimants',
                    'permission' => 'claimants.index',
                ],
                [
                    'key'        => 'settings.claim_payers',
                    'label'      => 'lookups::sidebar.Claim Payers',
                    'permission' => 'claim_payers.index',
                ],
                [
                    'key'        => 'settings.installment_types',
                    'label'      => 'lookups::sidebar.Installment Types',
                    'permission' => 'installment_types.index',
                ],
                [
                    'key'        => 'settings.installment_statuses',
                    'label'      => 'lookups::sidebar.Installment Statuses',
                    'permission' => 'installment_statuses.index',
                ],
                [
                    'key'        => 'settings.transaction_types',
                    'label'      => 'lookups::sidebar.Transaction Types',
                    'permission' => 'transaction_types.index',
                ],
                [
                    'key'        => 'settings.transaction_statuses',
                    'label'      => 'lookups::sidebar.Transaction Statuses',
                    'permission' => 'transaction_statuses.index',
                ],
                [
                    'key'        => 'settings.categories',
                    'label'      => 'lookups::sidebar.Categories',
                    'permission' => 'categories.index',
                ],
                [
                    'key'        => 'settings.companies',
                    'label'      => 'sidebar.Companies',
                    'permission' => 'companies.index',
                ],
                [
                    'key'        => 'settings.expense_types',
                    'label'      => 'expenses::types.index_title',
                    'permission' => 'expenses.expense-types.index',
                ],
                [
                    'key'        => 'settings.expense_periods',
                    'label'      => 'expenses::recurrence_periods.index_title',
                    'permission' => 'expenses.recurrence-periods.index',
                ],
                [
                    'key'        => 'settings.bank_accounts',
                    'label'      => 'sidebar.Bank Accounts',
                    'permission' => 'accounts.bank-accounts.index',
                ],
                [
                    'key'        => 'settings.safes',
                    'label'      => 'sidebar.Treasury Accounts',
                    'permission' => 'accounts.safes.index',
                ],
                [
                    'key'        => 'settings.product_types',
                    'label'      => 'lookups::sidebar.Product Types',
                    'permission' => 'product_types.index',
                ],
                [
                    'key'        => 'settings.roles',
                    'label'      => 'sidebar.Manage Roles',
                    'permission' => 'settings.roles.index',
                ],
                [
                    'key'        => 'settings.role_permissions',
                    'label'      => 'sidebar.Manage Role Permissions',
                    'permission' => 'settings.roles.permissions',
                ],
                [
                    'key'        => 'settings.permissions',
                    'label'      => 'sidebar.Manage Permissions',
                    'permission' => 'settings.permissions.index',
                ],
                [
                    'key'        => 'settings.sidebar_permissions',
                    'label'      => 'sidebar.Manage Sidebar Permissions',
                    'permission' => 'settings.sidebar-permissions.index',
                    'additional_permissions' => ['settings.sidebar-permissions.update'],
                ],
                [
                    'key'        => 'settings.users',
                    'label'      => 'sidebar.Assign Roles to Users',
                    'permission' => 'users.index',
                ],
            ],
        ],

        [
            'key'   => 'audit_logs',
            'label' => 'sidebar.Audit Logs',
            'items' => [
                [
                    'key'                    => 'audit.logs',
                    'label'                  => 'sidebar.Audit Logs',
                    'permission'             => 'audit.logs',
                    'additional_permissions' => ['audit.logs.show', 'audit.logs.purge', 'audit.logs.revert', 'view-audit-logs'],
                ],
            ],
        ],
    ],
];

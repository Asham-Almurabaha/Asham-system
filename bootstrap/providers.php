<?php

return [
    App\Providers\ViewServiceProvider::class,
    Modules\Accounts\Providers\AccountsServiceProvider::class,
    Modules\Lookups\Providers\LookupsServiceProvider::class,
    Modules\Customers\Providers\CustomersServiceProvider::class,
    Modules\Contracts\Providers\ContractsServiceProvider::class,
    Modules\Guarantors\Providers\GuarantorsServiceProvider::class,
    Modules\Investors\Providers\InvestorsServiceProvider::class,
];

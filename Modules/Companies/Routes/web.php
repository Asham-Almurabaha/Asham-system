<?php

use Illuminate\Support\Facades\Route;
use Modules\Companies\Http\Controllers\CompanyController;
use Modules\Companies\Http\Controllers\CompanyTransactionController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::resource('companies', CompanyController::class);
    Route::resource('company-transactions', CompanyTransactionController::class);
});

<?php

use Illuminate\Support\Facades\Route;
use Modules\Lookups\Http\Controllers\CategoryController;
use Modules\Lookups\Http\Controllers\ContractStatusController;
use Modules\Lookups\Http\Controllers\InstallmentStatusController;
use Modules\Lookups\Http\Controllers\InstallmentTypeController;
use Modules\Lookups\Http\Controllers\NationalityController;
use Modules\Lookups\Http\Controllers\TitleController;
use Modules\Lookups\Http\Controllers\TransactionStatusController;
use Modules\Lookups\Http\Controllers\TransactionTypeController;

Route::middleware(['web', 'auth'])->prefix('settings')->group(function () {
    Route::resource('nationalities', NationalityController::class);
    Route::resource('titles', TitleController::class);
    Route::resource('categories', CategoryController::class);
    Route::resource('contract_statuses', ContractStatusController::class);
    Route::resource('installment_statuses', InstallmentStatusController::class);
    Route::resource('installment_types', InstallmentTypeController::class);
    Route::resource('transaction_statuses', TransactionStatusController::class);
    Route::resource('transaction_types', TransactionTypeController::class);
});

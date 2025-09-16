<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Lookups\CategoryController;
use App\Http\Controllers\Lookups\ContractStatusController;
use App\Http\Controllers\Lookups\InstallmentStatusController;
use App\Http\Controllers\Lookups\InstallmentTypeController;
use App\Http\Controllers\Lookups\NationalityController;
use App\Http\Controllers\Lookups\TitleController;
use App\Http\Controllers\Lookups\TransactionStatusController;
use App\Http\Controllers\Lookups\TransactionTypeController;

Route::prefix('settings')->group(function () {
    Route::resource('nationalities', NationalityController::class);
    Route::resource('titles', TitleController::class);
    Route::resource('categories', CategoryController::class);
    Route::resource('contract_statuses', ContractStatusController::class);
    Route::resource('installment_statuses', InstallmentStatusController::class);
    Route::resource('installment_types', InstallmentTypeController::class);
    Route::resource('transaction_statuses', TransactionStatusController::class);
    Route::resource('transaction_types', TransactionTypeController::class);
});

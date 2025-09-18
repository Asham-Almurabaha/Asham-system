<?php

use Illuminate\Support\Facades\Route;
use Modules\Lookups\Http\Controllers\CategoryController;
use Modules\Lookups\Http\Controllers\ClaimFirstPartyController;
use Modules\Lookups\Http\Controllers\ClaimPayingPartyController;
use Modules\Lookups\Http\Controllers\ClaimStatusController;
use Modules\Lookups\Http\Controllers\CustomerStatusController;
use Modules\Lookups\Http\Controllers\ContractStatusController;
use Modules\Lookups\Http\Controllers\GuarantorStatusController;
use Modules\Lookups\Http\Controllers\InstallmentStatusController;
use Modules\Lookups\Http\Controllers\InstallmentTypeController;
use Modules\Lookups\Http\Controllers\NationalityController;
use Modules\Lookups\Http\Controllers\ProductTypeController;
use Modules\Lookups\Http\Controllers\TitleController;
use Modules\Lookups\Http\Controllers\TransactionStatusController;
use Modules\Lookups\Http\Controllers\TransactionTypeController;

Route::middleware(['web', 'auth'])->prefix('settings')->group(function () {
    Route::resource('nationalities', NationalityController::class);
    Route::resource('titles', TitleController::class);
    Route::resource('categories', CategoryController::class);
    Route::resource('guarantor_statuses', GuarantorStatusController::class);
    Route::resource('customer_statuses', CustomerStatusController::class);
    Route::resource('contract_statuses', ContractStatusController::class);
    Route::resource('claim_statuses', ClaimStatusController::class);
    Route::resource('claim_paying_parties', ClaimPayingPartyController::class);
    Route::resource('claim_first_parties', ClaimFirstPartyController::class);
    Route::resource('installment_statuses', InstallmentStatusController::class);
    Route::resource('installment_types', InstallmentTypeController::class);
    Route::resource('transaction_statuses', TransactionStatusController::class);
    Route::resource('transaction_types', TransactionTypeController::class);
    Route::resource('product_types', ProductTypeController::class);
});

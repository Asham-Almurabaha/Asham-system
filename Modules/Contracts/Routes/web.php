<?php

use Illuminate\Support\Facades\Route;
use Modules\Contracts\Http\Controllers\ContractController;
use Modules\Contracts\Http\Controllers\ContractInstallmentController;
use Modules\Contracts\Http\Controllers\ContractReportController;
use Modules\Contracts\Http\Controllers\ContractsImportController;

// استيراد العقود
Route::prefix('contracts/import')->name('contracts.')->group(function () {
    Route::get('/', [ContractsImportController::class, 'create'])->name('import.form');
    Route::post('/', [ContractsImportController::class, 'store'])->name('import');
    Route::get('/template', [ContractsImportController::class, 'template'])->name('import.template');
    Route::get('/failures/fix', [ContractsImportController::class, 'exportFailuresFix'])->name('import.failures.fix');
});

// CRUD العقود
Route::resource('contracts', ContractController::class);
Route::post('/contracts/investors/store', [ContractController::class, 'storeInvestors'])->name('contracts.investors.store');

// الأقساط
Route::prefix('installments')->name('installments.')->group(function () {
    Route::post('/pay', [ContractInstallmentController::class, 'payInstallment'])->name('pay');
    Route::post('/contracts/{contract}/early-settle', [ContractInstallmentController::class, 'earlySettle'])->name('early_settle');
});
Route::post('/installments/defer/{id}', [ContractInstallmentController::class, 'deferAjax']);
Route::post('/installments/excuse/{id}', [ContractInstallmentController::class, 'excuseAjax']);

// التقارير والعرض والطباعة
Route::get('reports/contracts/status/{status}', [ContractReportController::class, 'status'])->name('reports.contracts.status');
Route::get('reports/contracts/without-investor', [ContractReportController::class, 'withoutInvestor'])->name('reports.contracts.without_investor');
Route::get('/contracts/{contract}/print', [ContractReportController::class, 'show'])->name('contracts.print');
Route::get('/contracts/{contract}/closure', [ContractReportController::class, 'closure'])->name('contracts.closure');
Route::get('/contracts/{contract}/paid', [ContractReportController::class, 'paidInstallments'])->name('contracts.paid');


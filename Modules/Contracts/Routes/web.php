<?php

use Illuminate\Support\Facades\Route;
use Modules\Contracts\Http\Controllers\ContractController;
use Modules\Contracts\Http\Controllers\ContractInstallmentController;
use Modules\Contracts\Http\Controllers\ContractReportController;
use Modules\Contracts\Http\Controllers\ContractsImportController;
use Modules\Contracts\Http\Controllers\ContractsExportController;
use Modules\Contracts\Http\Controllers\ContractClaimController;

// استيراد العقود
Route::prefix('contracts/import')->name('contracts.')->group(function () {
    Route::get('/', [ContractsImportController::class, 'create'])->name('import.form');
    Route::post('/', [ContractsImportController::class, 'store'])->name('import');
    Route::get('/basic', [ContractsImportController::class, 'createBasic'])->name('import.basic.form');
    Route::post('/basic', [ContractsImportController::class, 'storeBasic'])->name('import.basic');
    Route::get('/investors', [ContractsImportController::class, 'createInvestors'])->name('import.investors.form');
    Route::post('/investors', [ContractsImportController::class, 'storeInvestors'])->name('import.investors');
    Route::get('/investors/failures/fix', [ContractsImportController::class, 'exportInvestorsFailuresFix'])->name('import.investors.failures.fix');
    Route::get('/payments', [ContractsImportController::class, 'createPayments'])->name('import.payments.form');
    Route::post('/payments', [ContractsImportController::class, 'storePayments'])->name('import.payments');
    Route::get('/payments/failures/fix', [ContractsImportController::class, 'exportPaymentsFailuresFix'])->name('import.payments.failures.fix');
    Route::get('/payments/skipped/export', [ContractsImportController::class, 'exportPaymentsSkipped'])->name('import.payments.skipped.export');

    Route::get('/template', [ContractsImportController::class, 'template'])->name('import.template');
    Route::get('/failures/fix', [ContractsImportController::class, 'exportFailuresFix'])->name('import.failures.fix');
    Route::get('/basic/failures/fix', [ContractsImportController::class, 'exportBasicFailuresFix'])->name('import.basic.failures.fix');
});

Route::patch('contract-claims/{contractClaim}/status', [ContractClaimController::class, 'updateStatus'])
    ->name('contract-claims.update-status');
Route::resource('contract-claims', ContractClaimController::class)->except(['show', 'create', 'edit']);

// CRUD العقود
Route::put('contracts/{contract}/images', [ContractController::class, 'updateImages'])
    ->name('contracts.images.update');
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
Route::get('reports/contracts/office-outstanding', [ContractReportController::class, 'officeOutstanding'])->name('reports.contracts.office_outstanding');
Route::get('reports/contracts/status/{status}', [ContractReportController::class, 'status'])->name('reports.contracts.status');
Route::get('reports/contracts/without-investor', [ContractReportController::class, 'withoutInvestor'])->name('reports.contracts.without_investor');
Route::get('/contracts/{contract}/print', [ContractReportController::class, 'show'])->name('contracts.print');
Route::get('/contracts/{contract}/closure', [ContractReportController::class, 'closure'])->name('contracts.closure');
Route::get('/contracts/{contract}/paid', [ContractReportController::class, 'paidInstallments'])->name('contracts.paid');

// تصدير أمثلة العقود
Route::get('contracts/export', [ContractsExportController::class, 'create'])->name('contracts.export.form');
Route::get('contracts/export/basic', [ContractsExportController::class, 'basic'])->name('contracts.export.basic');
Route::get('contracts/export/investors', [ContractsExportController::class, 'investors'])->name('contracts.export.investors');
Route::get('contracts/export/payments', [ContractsExportController::class, 'payments'])->name('contracts.export.payments');


<?php

use Illuminate\Support\Facades\Route;
use Modules\Investors\Http\Controllers\AjaxInvestorController;
use Modules\Investors\Http\Controllers\InvestorImportController;
use Modules\Investors\Http\Controllers\InvestorTransactionController;
use Modules\Investors\Http\Controllers\InvestorController;
use Modules\Investors\Http\Controllers\InvestorReportController;
use Modules\Investors\Http\Controllers\InvestorStatementController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::prefix('investors/import')->name('investors.')->group(function () {
        Route::get('/',               [InvestorImportController::class, 'create'])->name('import.form');
        Route::post('/',              [InvestorImportController::class, 'store'])->name('import');
        Route::get('/template',       [InvestorImportController::class, 'template'])->name('import.template');
        Route::get('/failures/fix',   [InvestorImportController::class, 'exportFailuresFix'])->name('import.failures.fix');
        Route::post('/pending/{token}/confirm', [InvestorImportController::class, 'confirmPending'])
            ->name('import.pending.confirm');
        Route::post('/pending/{token}/ignore', [InvestorImportController::class, 'ignorePending'])
            ->name('import.pending.ignore');
        Route::post('/pending/{token}/store-new', [InvestorImportController::class, 'storePendingAsNew'])
            ->name('import.pending.store-new');
    });

    Route::resource('investors', InvestorController::class);
    Route::resource('investor-transactions', InvestorTransactionController::class);

    Route::get('/investors/{investor}/cash', [AjaxInvestorController::class, 'liquidity'])
        ->name('investors.cash');
    Route::get('/investors/{investor}/liquidity', [AjaxInvestorController::class, 'liquidity'])
        ->name('investors.liquidity');
    Route::get('/investors/{investor}/statement', [InvestorReportController::class, 'statement'])
        ->name('investors.statement.statement');
    Route::get('investors/{investor}/withdrawals', [InvestorReportController::class, 'withdrawals'])
        ->name('investors.withdrawals.withdrawals');
    Route::get('investors/{investor}/deposits', [InvestorReportController::class, 'deposits'])
        ->name('investors.deposits.deposits');
    Route::get('investors/{investor}/transactions', [InvestorReportController::class, 'transactions'])
        ->name('investors.transactions.transactions');
    Route::get('reports/investors/outstanding', [InvestorReportController::class, 'outstanding'])
        ->name('reports.investors.outstanding');
    Route::get('reports/investors/Allliquidity', [InvestorReportController::class, 'allliquidity'])
        ->name('reports.investors.Allliquidity');

    Route::get('/ajax/investors/{investor}/liquidity', [AjaxInvestorController::class, 'liquidity'])
        ->name('ajax.investors.liquidity');
});

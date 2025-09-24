<?php

use Illuminate\Support\Facades\Route;
use Modules\Ledger\Http\Controllers\LedgerController;
use Modules\Ledger\Http\Controllers\LedgerEntriesImportController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::prefix('ledger/import')->name('ledger.')->group(function () {
        Route::get('/',               [LedgerEntriesImportController::class, 'create'])->name('import.form');
        Route::post('/',              [LedgerEntriesImportController::class, 'store'])->name('import');
        Route::get('/template',       [LedgerEntriesImportController::class, 'template'])->name('import.template');
        Route::get('/failures/fix',   [LedgerEntriesImportController::class, 'exportFailuresFix'])->name('import.failures.fix');
    });

    Route::prefix('ledger')->name('ledger.')->group(function () {
        Route::get('/',                 [LedgerController::class, 'index'])->name('index');
        Route::get('/create',           [LedgerController::class, 'create'])->name('create');
        Route::post('/',                [LedgerController::class, 'store'])->name('store');
        Route::get('/transfer/create',  [LedgerController::class, 'transferCreate'])->name('transfer.create');
        Route::post('/transfer',        [LedgerController::class, 'transferStore'])->name('transfer.store');
        Route::get('/split/create',     [LedgerController::class, 'splitCreate'])->name('split.create');
        Route::post('/split',           [LedgerController::class, 'splitStore'])->name('split.store');
        Route::get('/export',           [LedgerController::class, 'export'])->name('export');
    });
});

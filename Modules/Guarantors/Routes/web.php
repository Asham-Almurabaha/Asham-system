<?php

use Illuminate\Support\Facades\Route;
use Modules\Guarantors\Http\Controllers\GuarantorController;
use Modules\Guarantors\Http\Controllers\GuarantorImportController;

Route::middleware('auth')->group(function () {
    Route::prefix('guarantors/import')->name('guarantors.')->group(function () {
        Route::get('/', [GuarantorImportController::class, 'create'])->name('import.form');
        Route::post('/', [GuarantorImportController::class, 'store'])->name('import');
        Route::get('/template', [GuarantorImportController::class, 'template'])->name('import.template');
        Route::get('/failures/fix', [GuarantorImportController::class, 'exportFailuresFix'])->name('import.failures.fix');
    });

    Route::resource('guarantors', GuarantorController::class);
});

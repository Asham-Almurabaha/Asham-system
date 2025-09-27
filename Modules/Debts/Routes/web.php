<?php

use Illuminate\Support\Facades\Route;
use Modules\Debts\Http\Controllers\DebtController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::resource('debts', DebtController::class)->except(['show']);
    Route::post('debts/{debt}/payments', [DebtController::class, 'storePayment'])
        ->name('debts.payments.store')
        ->middleware('can:debts.edit');
});

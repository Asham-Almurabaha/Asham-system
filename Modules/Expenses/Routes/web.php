<?php

use Illuminate\Support\Facades\Route;
use Modules\Expenses\Http\Controllers\ExpenseController;
use Modules\Expenses\Http\Controllers\ExpenseCompletionController;
use Modules\Expenses\Http\Controllers\ExpensePaymentController;
use Modules\Expenses\Http\Controllers\ExpenseRecurrencePeriodController;
use Modules\Expenses\Http\Controllers\ExpenseTypeController;

Route::middleware(['web', 'auth', 'permission.route'])
    ->prefix('expenses')
    ->name('expenses.')
    ->group(function (): void {
        Route::resource('expense-types', ExpenseTypeController::class)->except(['show']);
        Route::resource('recurrence-periods', ExpenseRecurrencePeriodController::class)->except(['show']);
        Route::get('expenses/{expense}/payments/create', [ExpensePaymentController::class, 'create'])->name('payments.create');
        Route::post('expenses/{expense}/payments', [ExpensePaymentController::class, 'store'])->name('payments.store');
        Route::post('expenses/{expense}/complete', ExpenseCompletionController::class)->name('expenses.complete');
        Route::resource('expenses', ExpenseController::class)->except(['show']);
    });

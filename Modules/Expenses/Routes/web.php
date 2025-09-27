<?php

use Illuminate\Support\Facades\Route;
use Modules\Expenses\Http\Controllers\ExpenseController;
use Modules\Expenses\Http\Controllers\ExpenseTypeController;

Route::middleware(['web', 'auth', 'permission.route'])
    ->prefix('expenses')
    ->name('expenses.')
    ->group(function (): void {
        Route::resource('expense-types', ExpenseTypeController::class)->except(['show']);
        Route::resource('expenses', ExpenseController::class)->except(['show']);
    });

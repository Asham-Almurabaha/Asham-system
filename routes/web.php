<?php

use App\Http\Controllers\AjaxAccountController;
use App\Http\Controllers\AjaxInvestorController;
use App\Http\Controllers\AjaxProductTypeController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ContractInstallmentController;
use App\Http\Controllers\ContractPrintController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuarantorController;
use App\Http\Controllers\InvestorController;
use App\Http\Controllers\InvestorStatementController;
use App\Http\Controllers\InvestorTransactionController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Setting\CategoryController;
use App\Http\Controllers\Setting\ContractStatusController;
use App\Http\Controllers\Setting\InstallmentStatusController;
use App\Http\Controllers\Setting\InstallmentTypeController;
use App\Http\Controllers\Setting\NationalityController;
use App\Http\Controllers\Setting\ProductTransactionController;
use App\Http\Controllers\Setting\ProductTypeController;
use App\Http\Controllers\Setting\SettingController;
use App\Http\Controllers\Setting\TitleController;
use App\Http\Controllers\Setting\TransactionStatusController;
use App\Http\Controllers\Setting\TransactionTypeController;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Route;







Route::post('/lang/toggle', [LanguageController::class, 'toggle'])->name('lang.toggle');
Route::get('/lang/{locale}', [LanguageController::class, 'switch'])->name('lang.switch');


// Route::view('/', 'welcome');


Route::get('/', function () {
    return User::count() == 0
        ? redirect()->route('register')
        : redirect()->route('login');
});

Route::middleware('auth')->group(function () {

Route::get('/home', function () {
    return Setting::count() > 0
        ? redirect()->route('dashboard')
        : redirect()->route('settings.create');
})->middleware('auth')->name('home');

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('can:view-dashboard')->name('dashboard');

Route::prefix('settings')->group(function () {
    Route::resource('settings', SettingController::class);
    Route::resource('nationalities', NationalityController::class);
    Route::resource('titles', TitleController::class);
    Route::resource('contract_statuses', ContractStatusController::class);
    Route::resource('installment_statuses', InstallmentStatusController::class);
    Route::resource('installment_types', InstallmentTypeController::class);
    Route::resource('transaction_types', TransactionTypeController::class);
    Route::resource('transaction_statuses', TransactionStatusController::class);
    Route::resource('categories', CategoryController::class);
});

Route::resource('customers', CustomerController::class);
Route::resource('guarantors', GuarantorController::class);
Route::resource('investors', InvestorController::class);
Route::resource('contracts', ContractController::class);
Route::resource('investor-transactions', InvestorTransactionController::class);

Route::prefix('ledger')->name('ledger.')->group(function () {
    Route::get('/',            [LedgerController::class, 'index'])->name('index');

    // قيد عادي (بنك أو خزنة)
    Route::get('/create',      [LedgerController::class, 'create'])->name('create');
    Route::post('/',           [LedgerController::class, 'store'])->name('store');

    // تحويل داخلي بين حسابات المكتب
    Route::get('/transfer/create', [LedgerController::class, 'transferCreate'])->name('transfer.create');
    Route::post('/transfer',       [LedgerController::class, 'transferStore'])->name('transfer.store');

    // قيد مُجزّأ (جزء بنك + جزء خزنة)
    Route::get('/split/create',    [LedgerController::class, 'splitCreate'])->name('split.create');
    Route::post('/split',          [LedgerController::class, 'splitStore'])->name('split.store');
});


Route::prefix('installments')->name('installments.')->group(function () {
   
    Route::post('/pay', [ContractInstallmentController::class, 'payInstallment'])->name('pay');
    
    Route::post('/contracts/{contract}/early-settle', [ContractInstallmentController::class, 'earlySettle'])->name('early_settle');
    
});

    Route::get('/investors/{investor}/cash', [AjaxInvestorController::class, 'liquidity'])->name('investors.cash');

    Route::get('/investors/{investor}/liquidity', [AjaxInvestorController::class, 'liquidity'])->name('investors.liquidity');

    Route::get('/product-types/{productType}/available', [AjaxProductTypeController::class, 'available'])->name('product-types.available');

    Route::get('/ajax/investors/{investor}/liquidity', [AjaxInvestorController::class, 'liquidity'])
        ->name('ajax.investors.liquidity');

    Route::get('/contracts/{contract}/print', [ContractPrintController::class, 'show'])
        ->name('contracts.print');

    Route::get('/contracts/{contract}/closure', [ContractPrintController::class, 'closure'])
    ->name('contracts.closure');

    Route::get('/investors/{investor}/statement', [InvestorStatementController::class, 'show'])
    ->name('investors.statement.show');



    // المتاح في الحسابات (بنكي/خزنة) — لو مش موجودة عندك فعلًا
    Route::get('/ajax/accounts/availability', [AjaxAccountController::class, 'availability'])
        ->name('ajax.accounts.availability');

    Route::get('/ajax/accounts/availability-bulk', [AjaxAccountController::class, 'availabilityBulk'])
        ->name('ajax.accounts.availability.bulk');

    Route::post('/contracts/investors/store', [ContractController::class, 'storeInvestors'])
        ->name('contracts.investors.store');
        
    Route::post('/installments/defer/{id}', [ContractInstallmentController::class, 'deferAjax']);
    Route::post('/installments/excuse/{id}', [ContractInstallmentController::class, 'excuseAjax']);

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


require __DIR__.'/auth.php';

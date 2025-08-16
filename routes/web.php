<?php

use App\Http\Controllers\ContractController;
use App\Http\Controllers\ContractInstallmentController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GuarantorController;
use App\Http\Controllers\InstallmentController;
use App\Http\Controllers\InvestorController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Setting\BankCashAccountController;
use App\Http\Controllers\Setting\CategoryController;
use App\Http\Controllers\Setting\ContractStatusController;
use App\Http\Controllers\Setting\ContractTypeController;
use App\Http\Controllers\Setting\InstallmentStatusController;
use App\Http\Controllers\Setting\InstallmentTypeController;
use App\Http\Controllers\Setting\NationalityController;
use App\Http\Controllers\Setting\ProductController;
use App\Http\Controllers\Setting\ProductEntryController;
use App\Http\Controllers\Setting\SettingController;
use App\Http\Controllers\Setting\TitleController;
use App\Http\Controllers\Setting\TransactionStatusController;
use App\Http\Controllers\Setting\TransactionTypeController;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Route;




Route::post('/lang/toggle', [LanguageController::class, 'toggle'])->name('lang.toggle');
Route::get('/lang/{locale}', [LanguageController::class, 'switch'])->name('lang.switch');


Route::view('/', 'welcome');


Route::get('/d', function () {
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

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');



Route::prefix('settings')->middleware('auth')->group(function () {
    Route::resource('settings', SettingController::class);
    Route::resource('nationalities', NationalityController::class);
    Route::resource('titles', TitleController::class);
    Route::resource('contract_statuses', ContractStatusController::class);
    Route::resource('contract_types', ContractTypeController::class);
    Route::resource('installment_statuses', InstallmentStatusController::class);
    Route::resource('installment_types', InstallmentTypeController::class);
    Route::resource('products', ProductController::class);
    Route::resource('product_entries', ProductEntryController::class);
    Route::resource('bank_cash_accounts', BankCashAccountController::class);
    Route::resource('transaction_types', TransactionTypeController::class);
    Route::resource('transaction_statuses', TransactionStatusController::class);
    Route::resource('categories', CategoryController::class);
});


/*
|--------------------------------------------------------------------------
| العملاء، الكفلاء، المستثمرين، العقود
|--------------------------------------------------------------------------
*/
Route::resource('customers', CustomerController::class);
Route::resource('guarantors', GuarantorController::class);
Route::resource('investors', InvestorController::class);
Route::resource('contracts', ContractController::class);



Route::prefix('installments')->name('installments.')->group(function () {
    // عرض قسط واحد
    Route::get('/{installment}', [InstallmentController::class, 'show'])
        ->name('show');

    // تنفيذ السداد
    Route::post('/pay', [ContractInstallmentController::class, 'payInstallment'])
        ->name('pay');

    
    Route::post('/installments/{contract}/early-settle', [ContractInstallmentController::class, 'earlySettle'])
    ->name('early_settle');
    
    // حذف السداد
    Route::delete('/{installment}/payment/{paymentId}', [InstallmentController::class, 'deletePayment'])
        ->name('payment.delete');
});



Route::post('/contracts/investors/store', [ContractController::class, 'storeInvestors'])
    ->name('contracts.investors.store');
    
Route::post('/installments/defer/{id}', [ContractInstallmentController::class, 'deferAjax']);
Route::post('/installments/excuse/{id}', [ContractInstallmentController::class, 'excuseAjax']);




/*
|--------------------------------------------------------------------------
| البروفايل (Profile)
|--------------------------------------------------------------------------
*/
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});


require __DIR__.'/auth.php';

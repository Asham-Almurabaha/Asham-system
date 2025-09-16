<?php

use App\Http\Controllers\AjaxAccountController;
use App\Http\Controllers\AjaxProductTypeController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LanguageController;
// use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LedgerController;
use App\Http\Controllers\LedgerEntriesImportController;
use App\Http\Controllers\Setting\SettingController;
use App\Http\Controllers\UserRoleController; // ✅ لإدارة أدوار المستخدمين
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Route;


// لغة الواجهة
Route::post('/lang/toggle', [LanguageController::class, 'toggle'])->name('lang.toggle');
Route::get('/lang/{locale}', [LanguageController::class, 'switch'])->name('lang.switch');

// الصفحة الرئيسية (لو مفيش يوزر يوجّه للتسجيل، غير كده تسجيل الدخول)
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
    })->name('home');

    // الداشبورد (متزوّد عندك بـ can:view-dashboard)
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('can:view-dashboard')
        ->name('dashboard');

    // الإعدادات
    Route::prefix('settings')->group(function () {
        Route::resource('settings', SettingController::class);
    });

    // استيراد القيود
    Route::prefix('ledger/import')->name('ledger.')->group(function () {
        Route::get('/',               [LedgerEntriesImportController::class, 'create'])->name('import.form');
        Route::post('/',              [LedgerEntriesImportController::class, 'store'])->name('import');
        Route::get('/template',       [LedgerEntriesImportController::class, 'template'])->name('import.template');
        Route::get('/failures/fix',   [LedgerEntriesImportController::class, 'exportFailuresFix'])->name('import.failures.fix');
    });

    // CRUDات رئيسية
    require base_path('Modules/Customers/Routes/web.php');
    require base_path('Modules/Guarantors/Routes/web.php');
    require base_path('Modules/Investors/Routes/web.php');
    require base_path('Modules/Contracts/Routes/web.php');

    // القيود
    Route::prefix('ledger')->name('ledger.')->group(function () {
        Route::get('/',                 [LedgerController::class, 'index'])->name('index');
        Route::get('/create',           [LedgerController::class, 'create'])->name('create');
        Route::post('/',                [LedgerController::class, 'store'])->name('store');
        Route::get('/transfer/create',  [LedgerController::class, 'transferCreate'])->name('transfer.create');
        Route::post('/transfer',        [LedgerController::class, 'transferStore'])->name('transfer.store');
        Route::get('/split/create',     [LedgerController::class, 'splitCreate'])->name('split.create');
        Route::post('/split',           [LedgerController::class, 'splitStore'])->name('split.store');
    });
    // AJAX مساعدة
    Route::get('/product-types/{productType}/available', [AjaxProductTypeController::class, 'available'])
        ->name('product-types.available');

    // سجلات التدقيق
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit.logs');

    // المتاح في الحسابات (بنكي/خزنة)
    Route::get('/ajax/accounts/availability',      [AjaxAccountController::class, 'availability'])->name('ajax.accounts.availability');
    Route::get('/ajax/accounts/availability-bulk', [AjaxAccountController::class, 'availabilityBulk'])->name('ajax.accounts.availability.bulk');

    // مستثمرين العقد
    // أعذار/تأجيل الأقساط Ajax أصبحت ضمن وحدة العقود

    // // البروفايل
    // Route::get('/profile',   [ProfileController::class, 'edit'])->name('profile.edit');
    // Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    // Route::delete('/profile',[ProfileController::class, 'destroy'])->name('profile.destroy');

    // ✅ إدارة أدوار المستخدمين (محمية بدور admin)
    Route::middleware(['role:admin'])->group(function () {
        Route::get('/users',               [UserRoleController::class, 'index'])->name('users.index');
        Route::get('/users/{user}/roles',  [UserRoleController::class, 'edit'])->name('users.roles.edit');
        Route::put('/users/{user}/roles',  [UserRoleController::class, 'update'])->name('users.roles.update');
    });
});

require __DIR__.'/auth.php';

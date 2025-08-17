<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table) {
            $table->id();

            // معلومات تعريفية
            $table->string('name')->unique();                 // اسم الحساب (عرضي داخل النظام)
            $table->string('bank_name')->nullable();          // اسم البنك (CIB, QNB ..)
            $table->string('account_number')->nullable()->unique();
            $table->string('iban', 34)->nullable()->unique();

            // إعدادات مالية
            $table->decimal('opening_balance', 18, 2)->default(0); // رصيد افتتاحي
            $table->string('currency_code', 3)->default('SAR');    // العملة (اختياري)

            // حالة وملاحظات
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
        // إضافة القيم الافتراضية
        DB::table('bank_accounts')->insert([
            ['name' => 'الحساب الرئيسي', 'bank_name' => 'البنك الراجحي', 'account_number' => '1234567890', 'iban' => 'SA1234567890123456789012', 'opening_balance' => 0, 'currency_code' => 'SAR', 'is_active' => true],
            ['name' => 'حساب الفرع الثاني', 'bank_name' => 'البنك العربي', 'account_number' => '0987654321', 'iban' => 'SA0987654321098765432109', 'opening_balance' => 0, 'currency_code' => 'SAR', 'is_active' => true],
            ['name' => 'حساب احتياطي', 'bank_name' => null, 'account_number' => null, 'iban' => null, 'opening_balance' => 0, 'currency_code' => 'SAR', 'is_active' => false],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
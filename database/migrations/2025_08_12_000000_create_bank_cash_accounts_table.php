<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBankCashAccountsTable extends Migration
{
    public function up()
    {
        Schema::create('bank_cash_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم الحساب
            $table->enum('type', ['بنكي', 'كاش','النظام']); // نوع الحساب: بنك أو خزنة
            $table->string('account_number')->nullable(); // رقم الحساب (اختياري)
            $table->string('branch')->nullable(); // فرع البنك (اختياري)
            $table->decimal('balance', 15, 2)->default(0); // الرصيد الافتراضي 0
            $table->text('notes')->nullable(); // ملاحظات إضافية
            $table->boolean('active')->default(true); // حالة الحساب (نشط)
            $table->boolean('is_protected')->default(false);
            $table->timestamps();
        });

        // إضافة بعض الحسابات الافتراضية
        DB::table('bank_cash_accounts')->insert([
            ['name' => 'المؤسسة', 'type' => 'النظام', 'account_number' => '', 'branch' => '', 'balance' => 0, 'notes' => 'حساب النظام الاساسي', 'active' => true,  'is_protected' => true],
            ['name' => 'حساب البنك الرئيسي', 'type' => 'بنكي', 'account_number' => '', 'branch' => '', 'balance' => 0, 'notes' => '', 'active' => true,  'is_protected' => true],
            ['name' => 'خزنة المكتب', 'type' => 'كاش', 'account_number' => null, 'branch' => null, 'balance' => 0, 'notes' => '', 'active' => true,  'is_protected' => true],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('bank_cash_accounts');
    }
}

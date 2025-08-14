<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionTypesTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('transaction_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();         // اسم العملية، مثلاً debit, credit ...
            $table->boolean('is_protected')->default(false); // هل العملية محمية ولا يمكن حذفها
            $table->string('description')->nullable(); // وصف العملية اختياري
            $table->timestamps();
        });
        // إضافة القيم الافتراضية
        DB::table('transaction_types')->insert([
            ['name' => 'إيداع', 'is_protected' => true, 'description' => 'إيداع نقدي في الحساب'],
            ['name' => 'سحب', 'is_protected' => true, 'description' => 'سحب نقدي من الحساب'],
            ['name' => 'تحويل بين حسابات', 'is_protected' => true, 'description' => 'عملية تحويل بين حسابات'],
            ]);
        // تأكد من إضافة القيم الافتراضية المناسبة حسب احتياجاتك
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('transaction_types');
    }
}

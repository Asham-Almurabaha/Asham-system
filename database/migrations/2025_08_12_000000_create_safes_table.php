<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('safes', function (Blueprint $table) {
            $table->id();

            // معلومات تعريفية
            $table->string('name')->unique();        // اسم الخزنة (الفرع الرئيسي ..)
            $table->string('location')->nullable();  // مكان الخزنة (اختياري)

            // إعدادات مالية
            $table->decimal('opening_balance', 18, 2)->default(0);
            $table->string('currency_code', 3)->default('SAR');

            // حالة وملاحظات
            $table->boolean('is_active')->default(true)->index();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
        // إضافة القيم الافتراضية
        DB::table('safes')->insert([
            ['name' => 'الخزنة الرئيسية', 'location' => 'المكتب الرئيسي', 'opening_balance' => 0, 'currency_code' => 'SAR', 'is_active' => true],
            ['name' => 'خزنة الفرع الثاني', 'location' => 'فرع الرياض', 'opening_balance' => 0, 'currency_code' => 'SAR', 'is_active' => true],
            ['name' => 'خزنة احتياطية', 'location' => null, 'opening_balance' => 0, 'currency_code' => 'SAR', 'is_active' => false],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('safes');
    }
};
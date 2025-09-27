<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('expense_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('recurrence_interval')->nullable();
            $table->decimal('default_amount', 18, 2)->default(0);
            $table->string('currency_code', 3)->default('SAR');
            $table->boolean('is_recurring')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        DB::table('expense_types')->insert([
            [
                'name' => 'الإيجارات',
                'recurrence_interval' => 'monthly',
                'default_amount' => 0,
                'currency_code' => 'SAR',
                'is_recurring' => true,
                'description' => 'المصروفات الخاصة بإيجار المكاتب أو الفروع',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'أقساط السيارات',
                'recurrence_interval' => 'monthly',
                'default_amount' => 0,
                'currency_code' => 'SAR',
                'is_recurring' => true,
                'description' => 'الأقساط الشهرية لتمويل السيارات التابعة للشركة',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'أقساط القروض',
                'recurrence_interval' => 'monthly',
                'default_amount' => 0,
                'currency_code' => 'SAR',
                'is_recurring' => true,
                'description' => 'أقساط التمويلات أو القروض البنكية',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'الرواتب',
                'recurrence_interval' => 'monthly',
                'default_amount' => 0,
                'currency_code' => 'SAR',
                'is_recurring' => true,
                'description' => 'رواتب وأجور الموظفين والعاملين',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'مصروفات أخرى',
                'recurrence_interval' => null,
                'default_amount' => 0,
                'currency_code' => 'SAR',
                'is_recurring' => false,
                'description' => 'مصروفات متنوعة لا تنتمي للأنواع الرئيسية',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_types');
    }
};

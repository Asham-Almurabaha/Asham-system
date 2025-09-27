<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('expense_recurrence_periods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name');
            $table->boolean('is_protected')->default(true);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('expense_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->foreignId('expense_recurrence_period_id')
                ->nullable()
                ->constrained('expense_recurrence_periods')
                ->nullOnDelete();
            $table->decimal('default_amount', 18, 2)->default(0);
            $table->string('currency_code', 3)->default('SAR');
            $table->boolean('is_recurring')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        $now = now();

        $periodIds = [];
        foreach ([
            'monthly' => [
                'code' => 'monthly',
                'name' => 'شهري',
                'description' => 'يتكرر كل شهر',
            ],
            'semi_annual' => [
                'code' => 'semi_annual',
                'name' => 'نصف سنوي',
                'description' => 'يتكرر مرتين في السنة',
            ],
            'other' => [
                'code' => 'other',
                'name' => 'أخرى',
                'description' => 'فترة تكرار مخصصة أو غير منتظمة',
            ],
        ] as $code => $period) {
            $periodIds[$code] = DB::table('expense_recurrence_periods')->insertGetId([
                'code' => $period['code'],
                'name' => $period['name'],
                'description' => $period['description'],
                'is_protected' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('expense_types')->insert([
            [
                'name' => 'الإيجارات',
                'expense_recurrence_period_id' => $periodIds['monthly'] ?? null,
                'default_amount' => 0,
                'currency_code' => 'SAR',
                'is_recurring' => true,
                'description' => 'المصروفات الخاصة بإيجار المكاتب أو الفروع',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'أقساط السيارات',
                'expense_recurrence_period_id' => $periodIds['monthly'] ?? null,
                'default_amount' => 0,
                'currency_code' => 'SAR',
                'is_recurring' => true,
                'description' => 'الأقساط الشهرية لتمويل السيارات التابعة للشركة',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'أقساط القروض',
                'expense_recurrence_period_id' => $periodIds['monthly'] ?? null,
                'default_amount' => 0,
                'currency_code' => 'SAR',
                'is_recurring' => true,
                'description' => 'أقساط التمويلات أو القروض البنكية',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'الرواتب',
                'expense_recurrence_period_id' => $periodIds['monthly'] ?? null,
                'default_amount' => 0,
                'currency_code' => 'SAR',
                'is_recurring' => true,
                'description' => 'رواتب وأجور الموظفين والعاملين',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'مصروفات أخرى',
                'expense_recurrence_period_id' => $periodIds['other'] ?? null,
                'default_amount' => 0,
                'currency_code' => 'SAR',
                'is_recurring' => false,
                'description' => 'مصروفات متنوعة لا تنتمي للأنواع الرئيسية',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_types');
        Schema::dropIfExists('expense_recurrence_periods');
    }
};

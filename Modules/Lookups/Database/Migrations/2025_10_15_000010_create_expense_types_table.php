<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_types');
        Schema::dropIfExists('expense_recurrence_periods');
    }
};

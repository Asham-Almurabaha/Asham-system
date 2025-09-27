<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_type_id')->constrained('expense_types')->cascadeOnDelete();
            $table->string('title');
            $table->decimal('amount', 18, 2);
            $table->string('currency_code', 3)->default('SAR');
            $table->date('due_date')->index();
            $table->date('paid_at')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};

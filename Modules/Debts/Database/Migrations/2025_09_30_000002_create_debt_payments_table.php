<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('debt_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debt_id')->constrained('debts')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->date('paid_at');
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->foreignId('safe_id')->nullable()->constrained('safes')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['paid_at']);
            $table->index(['bank_account_id', 'safe_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debt_payments');
    }
};

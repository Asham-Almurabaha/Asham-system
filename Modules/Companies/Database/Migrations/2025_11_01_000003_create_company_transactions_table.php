<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('company_transactions', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->decimal('total_amount', 18, 2);
            $table->foreignId('status_id')
                ->constrained('transaction_statuses')
                ->cascadeOnDelete();
            $table->foreignId('bank_account_id')
                ->nullable()
                ->constrained('bank_accounts')
                ->nullOnDelete();
            $table->foreignId('safe_id')
                ->nullable()
                ->constrained('safes')
                ->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['transaction_date']);
            $table->index(['bank_account_id', 'safe_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_transactions');
    }
};

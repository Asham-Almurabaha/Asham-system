<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('debts', function (Blueprint $table) {
            $table->id();
            $table->enum('party_type', ['customer', 'investor', 'other'])->index();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('investor_id')->nullable()->constrained('investors')->nullOnDelete();
            $table->string('counterparty_name')->nullable();
            $table->decimal('principal_amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->foreignId('safe_id')->nullable()->constrained('safes')->nullOnDelete();
            $table->date('issued_at');
            $table->date('due_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['issued_at', 'due_at']);
            $table->index(['bank_account_id', 'safe_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debts');
    }
};

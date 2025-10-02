<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('company_transaction_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_transaction_id')
                ->constrained('company_transactions')
                ->cascadeOnDelete();
            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();
            $table->decimal('share_percentage', 5, 2)->nullable();
            $table->decimal('share_amount', 18, 2);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['company_transaction_id', 'company_id'], 'company_allocation_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_transaction_allocations');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contract_claim_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_claim_id')->constrained()->cascadeOnDelete();
            $table->foreignId('claim_status_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('amount', 12, 2)->default(0);
            $table->date('paid_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_claim_payments');
    }
};

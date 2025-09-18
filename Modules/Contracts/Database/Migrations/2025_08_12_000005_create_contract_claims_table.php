<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('contract_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->enum('filed_party_role', ['customer', 'guarantor']);
            $table->decimal('claim_amount', 15, 2);
            $table->date('claim_date');
            $table->string('document_number');
            $table->timestamps();

            $table->index('claim_date');
            $table->index('document_number');
            $table->index('filed_party_role');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_claims');
    }
};

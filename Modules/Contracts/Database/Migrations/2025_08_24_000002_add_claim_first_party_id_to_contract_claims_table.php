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
        Schema::table('contract_claims', function (Blueprint $table) {
            $table->foreignId('claim_first_party_id')
                ->nullable()
                ->after('contract_id')
                ->constrained('claim_first_parties')
                ->nullOnDelete();

            $table->index('claim_first_party_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_claims', function (Blueprint $table) {
            $table->dropForeign(['claim_first_party_id']);
            $table->dropIndex(['claim_first_party_id']);
            $table->dropColumn('claim_first_party_id');
        });
    }
};

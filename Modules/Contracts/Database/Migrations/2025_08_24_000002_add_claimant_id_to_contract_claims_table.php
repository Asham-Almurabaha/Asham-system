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
            $table->foreignId('claimant_id')
                ->nullable()
                ->after('contract_id')
                ->constrained('claimants')
                ->nullOnDelete();

            $table->index('claimant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_claims', function (Blueprint $table) {
            $table->dropForeign(['claimant_id']);
            $table->dropIndex(['claimant_id']);
            $table->dropColumn('claimant_id');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('contract_claim_payments', 'claim_status_id')) {
            Schema::table('contract_claim_payments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('claim_status_id');
            });
        }

        if (! Schema::hasColumn('contract_claim_payments', 'claim_payer_id')) {
            Schema::table('contract_claim_payments', function (Blueprint $table) {
                $table->foreignId('claim_payer_id')
                    ->nullable()
                    ->after('contract_claim_id')
                    ->constrained('claim_payers')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('contract_claim_payments', 'claim_payer_id')) {
            Schema::table('contract_claim_payments', function (Blueprint $table) {
                $table->dropConstrainedForeignId('claim_payer_id');
            });
        }

        if (! Schema::hasColumn('contract_claim_payments', 'claim_status_id')) {
            Schema::table('contract_claim_payments', function (Blueprint $table) {
                $table->foreignId('claim_status_id')
                    ->nullable()
                    ->after('contract_claim_id')
                    ->constrained('claim_statuses')
                    ->nullOnDelete();
            });
        }
    }
};

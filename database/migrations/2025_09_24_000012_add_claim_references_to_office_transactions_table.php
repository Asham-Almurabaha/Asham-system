<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('office_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('office_transactions', 'contract_claim_id')) {
                $table->foreignId('contract_claim_id')
                    ->nullable()
                    ->after('contract_id')
                    ->constrained('contract_claims')
                    ->nullOnDelete();
            }

            if (! Schema::hasColumn('office_transactions', 'contract_claim_payment_id')) {
                $table->foreignId('contract_claim_payment_id')
                    ->nullable()
                    ->after('contract_claim_id')
                    ->constrained('contract_claim_payments')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('office_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('office_transactions', 'contract_claim_payment_id')) {
                $table->dropForeign(['contract_claim_payment_id']);
                $table->dropColumn('contract_claim_payment_id');
            }

            if (Schema::hasColumn('office_transactions', 'contract_claim_id')) {
                $table->dropForeign(['contract_claim_id']);
                $table->dropColumn('contract_claim_id');
            }
        });
    }
};

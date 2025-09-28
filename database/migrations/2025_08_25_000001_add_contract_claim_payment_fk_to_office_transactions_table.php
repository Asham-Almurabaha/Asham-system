<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('office_transactions') || ! Schema::hasTable('contract_claim_payments')) {
            return;
        }

        Schema::table('office_transactions', function (Blueprint $table) {
            $table->foreign('contract_claim_payment_id')
                ->references('id')
                ->on('contract_claim_payments')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('office_transactions')) {
            return;
        }

        Schema::table('office_transactions', function (Blueprint $table) {
            $table->dropForeign(['contract_claim_payment_id']);
        });
    }
};

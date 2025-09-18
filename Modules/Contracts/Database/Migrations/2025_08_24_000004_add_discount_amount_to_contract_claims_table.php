<?php

use Illuminate\\Database\\Migrations\\Migration;
use Illuminate\\Database\\Schema\\Blueprint;
use Illuminate\\Support\\Facades\\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('contract_claims', function (Blueprint $table) {
            if (! Schema::hasColumn('contract_claims', 'discount_amount')) {
                $table->decimal('discount_amount', 15, 2)->default(0)->after('claim_amount');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract_claims', function (Blueprint $table) {
            if (Schema::hasColumn('contract_claims', 'discount_amount')) {
                $table->dropColumn('discount_amount');
            }
        });
    }
};

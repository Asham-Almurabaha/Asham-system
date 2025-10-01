<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('company_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('company_transactions', 'bank_amount')) {
                $table->decimal('bank_amount', 18, 2)->default(0)->after('bank_account_id');
            }

            if (!Schema::hasColumn('company_transactions', 'safe_amount')) {
                $table->decimal('safe_amount', 18, 2)->default(0)->after('safe_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('company_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('company_transactions', 'bank_amount')) {
                $table->dropColumn('bank_amount');
            }

            if (Schema::hasColumn('company_transactions', 'safe_amount')) {
                $table->dropColumn('safe_amount');
            }
        });
    }
};

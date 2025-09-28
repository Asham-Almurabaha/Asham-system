<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('expenses', 'manual_paid_amount')) {
                $table->decimal('manual_paid_amount', 18, 2)->nullable()->after('amount');
            }

            if (! Schema::hasColumn('expenses', 'manual_outstanding_amount')) {
                $table->decimal('manual_outstanding_amount', 18, 2)->nullable()->after('manual_paid_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            if (Schema::hasColumn('expenses', 'manual_outstanding_amount')) {
                $table->dropColumn('manual_outstanding_amount');
            }

            if (Schema::hasColumn('expenses', 'manual_paid_amount')) {
                $table->dropColumn('manual_paid_amount');
            }
        });
    }
};

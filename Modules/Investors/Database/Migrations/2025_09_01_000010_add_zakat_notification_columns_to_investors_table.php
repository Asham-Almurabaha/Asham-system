<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('investors', function (Blueprint $table) {
            if (!Schema::hasColumn('investors', 'zakat_last_notified_due_date')) {
                $table->date('zakat_last_notified_due_date')->nullable()->after('investment_start_date');
            }

            if (!Schema::hasColumn('investors', 'zakat_last_notified_at')) {
                $table->timestamp('zakat_last_notified_at')->nullable()->after('zakat_last_notified_due_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('investors', function (Blueprint $table) {
            if (Schema::hasColumn('investors', 'zakat_last_notified_at')) {
                $table->dropColumn('zakat_last_notified_at');
            }

            if (Schema::hasColumn('investors', 'zakat_last_notified_due_date')) {
                $table->dropColumn('zakat_last_notified_due_date');
            }
        });
    }
};

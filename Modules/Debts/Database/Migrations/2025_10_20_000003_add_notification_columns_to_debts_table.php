<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            if (! Schema::hasColumn('debts', 'last_notified_at')) {
                $table->timestamp('last_notified_at')->nullable()->after('notes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('debts', function (Blueprint $table) {
            if (Schema::hasColumn('debts', 'last_notified_at')) {
                $table->dropColumn('last_notified_at');
            }
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('guarantors')) {
            return;
        }

        Schema::table('guarantors', function (Blueprint $table) {
            if (!Schema::hasColumn('guarantors', 'guarantor_status_id')) {
                $table->foreignId('guarantor_status_id')
                    ->nullable()
                    ->after('title_id')
                    ->constrained('guarantor_statuses')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('guarantors')) {
            return;
        }

        Schema::table('guarantors', function (Blueprint $table) {
            if (Schema::hasColumn('guarantors', 'guarantor_status_id')) {
                $table->dropForeign(['guarantor_status_id']);
                $table->dropColumn('guarantor_status_id');
            }
        });
    }
};

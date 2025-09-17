<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'customer_status_id')) {
                $table->foreignId('customer_status_id')
                    ->nullable()
                    ->after('title_id')
                    ->constrained('customer_statuses')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('customers')) {
            return;
        }

        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'customer_status_id')) {
                $table->dropForeign(['customer_status_id']);
                $table->dropColumn('customer_status_id');
            }
        });
    }
};

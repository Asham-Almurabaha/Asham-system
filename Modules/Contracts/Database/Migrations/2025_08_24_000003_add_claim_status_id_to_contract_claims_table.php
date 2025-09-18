<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('contract_claims', 'claim_status_id')) {
            Schema::table('contract_claims', function (Blueprint $table) {
                $table->foreignId('claim_status_id')
                    ->nullable()
                    ->after('document_number')
                    ->constrained('claim_statuses')
                    ->nullOnDelete();
            });
        }

        $statusId = DB::table('claim_statuses')
            ->where('name', 'قيد المراجعة')
            ->value('id');

        if ($statusId) {
            DB::table('contract_claims')
                ->whereNull('claim_status_id')
                ->update(['claim_status_id' => $statusId]);
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('contract_claims', 'claim_status_id')) {
            Schema::table('contract_claims', function (Blueprint $table) {
                $table->dropConstrainedForeignId('claim_status_id');
            });
        }
    }
};

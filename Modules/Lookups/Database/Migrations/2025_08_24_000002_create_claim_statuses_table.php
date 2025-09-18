<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateClaimStatusesTable extends Migration
{
    public function up(): void
    {
        Schema::create('claim_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_protected')->default(false);
            $table->timestamps();
        });

        DB::table('claim_statuses')->insert([
            ['name' => 'مدفوع كامل', 'is_protected' => true],
            ['name' => 'مدفوع جزئي', 'is_protected' => true],
            ['name' => 'مرفوض', 'is_protected' => true],
            ['name' => 'مقبول', 'is_protected' => true],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_statuses');
    }
}

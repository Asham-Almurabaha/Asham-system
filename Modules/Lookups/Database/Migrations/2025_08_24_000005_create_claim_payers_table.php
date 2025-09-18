<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('claim_payers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_protected')->default(false);
            $table->timestamps();
        });

        DB::table('claim_payers')->insert([
            ['name' => 'المحكمة', 'is_protected' => true],
            ['name' => 'العميل', 'is_protected' => true],
            ['name' => 'الكفيل', 'is_protected' => true],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('claim_payers');
    }
};

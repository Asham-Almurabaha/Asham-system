<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateGuarantorStatusesTable extends Migration
{
    public function up()
    {
        Schema::create('guarantor_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_protected')->default(false);
            $table->timestamps();
        });

        DB::table('guarantor_statuses')->insert([
            ['name' => 'مرفوع فيه', 'is_protected' => true],
            ['name' => 'ملتزم', 'is_protected' => true],
            ['name' => 'قائمة سوداء', 'is_protected' => true],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('guarantor_statuses');
    }
}

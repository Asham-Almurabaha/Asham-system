<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInstallmentStatusesTable extends Migration
{
    public function up()
    {
        Schema::create('installment_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // معلق، مدفوع، جزئي، متأخر، مدفوع مبكر
            $table->boolean('is_protected')->default(false);
            $table->timestamps();
        });

        DB::table('installment_statuses')->insert([
            ['name'=> 'لم يحل','is_protected'=> true],
            ['name'=> 'متأخر','is_protected'=> true],
            ['name' => 'مؤجل','is_protected' => true],
            ['name' => 'معتذر','is_protected' => true],
            ['name'=> 'مستحق','is_protected'=> true],

            ['name' => 'مدفوع كامل','is_protected' => true],
            ['name' => 'مدفوع مبكر','is_protected' => true],
            ['name' => 'مدفوع جزئي','is_protected' => true],
            ['name' => 'مدفوع متأخر','is_protected' => true],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('installment_statuses');
    }
}

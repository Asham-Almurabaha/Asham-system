<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInstallmentTypesTable extends Migration
{
    public function up()
    {
        Schema::create('installment_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // يومي، اسبوعي، شهري
            $table->boolean('is_protected')->default(false);
            $table->timestamps();
        });

        DB::table('installment_types')->insert([
            ['name' => 'سنوي','is_protected' => true],
            ['name' => 'شهري','is_protected' => true],
            ['name' => 'اسبوعي','is_protected' => true],
            ['name' => 'يومي','is_protected' => true],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('installment_types');
    }
}

<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractTypesTable extends Migration
{
    public function up()
    {
        Schema::create('contract_types', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->boolean('is_protected')->default(false);
            $table->timestamps();
        });

        DB::table('contract_types')->insert([
            ['name' => 'بطاقات',  'is_protected' => true],
            ['name' => 'جوال',  'is_protected' => true],
            ['name' => 'سيارة',  'is_protected' => true],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('contract_types');
    }
}


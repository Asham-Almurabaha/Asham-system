<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateNationalitiesTable extends Migration
{
    public function up()
    {
        Schema::create('nationalities', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        DB::table('nationalities')->insert([
            ['name' => 'مصري'],
            ['name' => 'سعودي'],
            ['name' => 'سوري'],
            ['name' => 'سوداني'],
            ['name' => 'باكستاني'],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('nationalities');
    }
}

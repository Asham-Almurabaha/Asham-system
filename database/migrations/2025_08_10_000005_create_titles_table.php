<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTitlesTable extends Migration
{
    public function up()
    {
        Schema::create('titles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        DB::table('titles')->insert([
            ['name' => 'حكومي'],
            ['name' => 'عسكري'],
            ['name' => 'قطاع خاص'],
            ['name' => 'مقيم'],
            ['name' => 'لا يعمل'],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('titles');
    }
}

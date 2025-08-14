<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersTable extends Migration
{
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('title_id')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('nationality_id')->nullable()->constrained('nationalities')->onDelete('set null');
            $table->foreign('title_id')->references('id')->on('titles')->onDelete('set null');
            $table->string('id_card_image')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        
        // ✅ إضافة عميل افتراضي
        DB::table('customers')->insert([
            'name' => 'عميل افتراضي',
            'phone' => '0500000000',
            'email' => 'default@example.com',
            'address' => 'الرياض - السعودية',
            'nationality_id' => null,
            'title_id' => null,
            'id_card_image' => null,
            'notes' => 'هذا عميل تم إضافته تلقائيًا',
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('customers');
    }
}

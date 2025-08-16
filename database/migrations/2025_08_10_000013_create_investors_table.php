<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvestorsTable extends Migration
{
    public function up()
    {
        Schema::create('investors', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('national_id')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('nationality_id')->nullable()->constrained('nationalities')->onDelete('set null');
            $table->foreignId('title_id')->nullable()->constrained('titles')->onDelete('set null');
            $table->string('id_card_image')->nullable();
            $table->string('contract_image')->nullable();
            $table->decimal('office_share_percentage', 5, 2)->default(0);
            $table->timestamps();
        });
        // ✅ إضافة مستثمر افتراضي
        DB::table('investors')->insert([
            ['name' => 'مستثمر افتراضي 1',
            'national_id' => '0000000000',
            'phone' => '0500000000',
            'email' => 'default@example.com',
            'address' => 'الرياض - السعودية',
            'nationality_id' => null,
            'title_id' => null,
            'id_card_image' => null,
            'contract_image' => null,
            'office_share_percentage' => 25,],
            ['name' => 'مستثمر افتراضي 2',
            'national_id' => '0000000000',
            'phone' => '0500000000',
            'email' => 'default@example.com',
            'address' => 'الرياض - السعودية',
            'nationality_id' => null,
            'title_id' => null,
            'id_card_image' => null,
            'contract_image' => null,
            'office_share_percentage' => 30,]
        ]);
            


    }

    public function down()
    {
        Schema::dropIfExists('investors');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم المنتج
            $table->boolean('is_protected')->default(false);
            $table->timestamps();
        });
        DB::table('product_types')->insert([
            ['name' => 'بطاقات',  'is_protected' => true],
            ['name' => 'جوال',  'is_protected' => true],
            ['name' => 'سيارة',  'is_protected' => true],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('product_types');
    }
};

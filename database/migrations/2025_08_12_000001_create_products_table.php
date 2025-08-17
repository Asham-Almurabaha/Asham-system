<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم المنتج
            $table->string('sku')->nullable(); // كود المنتج
            $table->text('description')->nullable(); // وصف المنتج
            $table->boolean('is_protected')->default(false);
            $table->timestamps();
        });
        DB::table('products')->insert([
            ['name' => 'بطاقات',  'is_protected' => true],
            ['name' => 'جوال',  'is_protected' => true],
            ['name' => 'سيارة',  'is_protected' => true],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};

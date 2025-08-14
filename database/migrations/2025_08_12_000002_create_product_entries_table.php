<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade'); // المنتج المرتبط
            $table->integer('quantity'); // الكمية
            $table->decimal('purchase_price', 10, 2); // سعر الشراء للوحدة
            $table->date('entry_date')->default(now()); // تاريخ الإدخال
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_entries');
    }
};

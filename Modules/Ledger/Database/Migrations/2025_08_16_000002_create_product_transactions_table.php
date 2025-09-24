<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('product_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_type_id')->constrained('product_types')->nullable()->onDelete('cascade'); // المنتج المرتبط
            $table->foreignId('ledger_entry_id')->constrained('ledger_entries')->nullable()->onDelete('cascade'); // ربط بالقيد
            $table->integer('quantity'); // الكمية
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_transactions');
    }
};

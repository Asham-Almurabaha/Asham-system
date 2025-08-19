<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoryTransactionStatusTable extends Migration
{
    public function up()
    {
        Schema::create('category_transaction_status', function (Blueprint $table) {
            $table->id();

            $table->foreignId('transaction_status_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->boolean('is_protected')->default(false); // هل العملية محمية ولا يمكن حذفها

            // اسم مفتاح فريد مختصر
            $table->unique(['transaction_status_id', 'category_id'], 'cat_trans_status_unique');

            $table->timestamps();
        });

        // إضافة القيم الافتراضية
        DB::table('category_transaction_status')->insert([
            ['transaction_status_id' => 3, 'category_id' => 1, 'is_protected' => true],
            ['transaction_status_id' => 4, 'category_id' => 1, 'is_protected' => true],
            ['transaction_status_id' => 8, 'category_id' => 1, 'is_protected' => true],
            ['transaction_status_id' => 9, 'category_id' => 1, 'is_protected' => true],
            ['transaction_status_id' => 1, 'category_id' => 3, 'is_protected' => true],
            ['transaction_status_id' => 2, 'category_id' => 3, 'is_protected' => true],
            ['transaction_status_id' => 3, 'category_id' => 3, 'is_protected' => true],
            ['transaction_status_id' => 1, 'category_id' => 4, 'is_protected' => true],
            ['transaction_status_id' => 2, 'category_id' => 4, 'is_protected' => true],
            ['transaction_status_id' => 5, 'category_id' => 4, 'is_protected' => true],
            ['transaction_status_id' => 6, 'category_id' => 4, 'is_protected' => true],
            ['transaction_status_id' => 10, 'category_id' => 4, 'is_protected' => true],
            ['transaction_status_id' => 11, 'category_id' => 4, 'is_protected' => true],
            ['transaction_status_id' => 12, 'category_id' => 4, 'is_protected' => true],
            
        ]); 
    }

    public function down()
    {
        Schema::dropIfExists('category_transaction_status');
    }
}

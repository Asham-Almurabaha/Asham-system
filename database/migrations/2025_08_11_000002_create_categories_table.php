<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCategoriesTable extends Migration
{
    public function up()
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();  // اسم المجال مثل: عقود، مستثمرين، الخ
            $table->unsignedBigInteger(column: 'transaction_type_id'); // المفتاح الأجنبي لنوع العملية
            $table->boolean('is_protected')->default(false); // هل العملية محمية ولا يمكن حذفها

            $table->timestamps();
        });
        // إضافة القيم الافتراضية
        DB::table('categories')->insert([
            ['name' => 'مستثمرين', 'transaction_type_id' => 1, 'is_protected' => true],
            ['name' => 'عقود', 'transaction_type_id' => 1, 'is_protected' => true],
            ['name' => 'اقساط', 'transaction_type_id' => 1, 'is_protected' => true],
            ['name' => 'حسابات', 'transaction_type_id' => 1, 'is_protected' => true],
            ]);
        // تأكد من إضافة القيم الافتراضية المناسبة حسب احتياجاتك
    }

    public function down()
    {
        Schema::dropIfExists('categories');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // 👈 مهم

class CreateContractStatusesTable extends Migration
{
    public function up()
    {
        Schema::create('contract_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique(); // نشط، مكتمل، ملغي...
            $table->boolean('is_protected')->default(false);
            $table->timestamps();
        });

        // إضافة القيم الافتراضية
        DB::table('contract_statuses')->insert([
            ['name' => 'بدون مستثمر',  'is_protected' => true],
            ['name' => 'معلق',  'is_protected' => true],
            ['name' => 'جديد',  'is_protected' => true],
            
            ['name' => 'منتهي',  'is_protected' => true],
            ['name' => 'سداد مبكر',  'is_protected' => true],
            ['name' => 'مطلوب',  'is_protected' => true],
            

            ['name' => 'منتظم',  'is_protected' => true],
            ['name' => 'غير منتظم',  'is_protected' => true],
            ['name' => 'متأخر',  'is_protected' => true],
            ['name' => 'متعثر',  'is_protected' => true],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('contract_statuses');
    }
}

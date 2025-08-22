<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionStatusesTable extends Migration
{
    public function up()
    {
        Schema::create('transaction_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // اسم الحالة مثل شراء، بيع، سداد ...
            $table->unsignedBigInteger(column: 'transaction_type_id'); // المفتاح الأجنبي لنوع العملية
            $table->boolean('is_protected')->default(false); // هل العملية محمية ولا يمكن حذفها

            $table->timestamps();

            // تعريف المفتاح الأجنبي والربط مع جدول transaction_types
            $table->foreign('transaction_type_id')
                  ->references('id')
                  ->on('transaction_types')
                  ->onDelete('cascade');  // لو حذفت نوع العملية، يتم حذف الحالات المرتبطة بها
        });
        // إضافة القيم الافتراضية
        DB::table('transaction_statuses')->insert([
            ['name' => 'شراء بضائع', 'transaction_type_id' => 2, 'is_protected' => true],
            ['name' => 'بيع بضائع', 'transaction_type_id' => 1, 'is_protected' => true],
            ['name' => 'إضافة عقد', 'transaction_type_id' => 2, 'is_protected' => true],
            ['name' => 'سداد قسط', 'transaction_type_id' => 1, 'is_protected' => true],
            ['name' => 'المكاتبة', 'transaction_type_id' => 1, 'is_protected' => true],
            ['name' => 'فرق البيع', 'transaction_type_id' => 1, 'is_protected' => true],
            ['name' => 'ربح المكتب', 'transaction_type_id' => 1, 'is_protected' => true],
            ['name' => 'سحب سيولة', 'transaction_type_id' => 2, 'is_protected' => true],
            ['name' => 'إضافة سيولة', 'transaction_type_id' => 1, 'is_protected' => true],
            ['name'=> 'إيداع حسابات', 'transaction_type_id' => 1, 'is_protected' => true],
            ['name'=> 'سحب حسابات', 'transaction_type_id' => 2, 'is_protected' => true],
            ['name'=> 'تحويل بين حسابات', 'transaction_type_id' => 3, 'is_protected' => true],
            ['name' => 'رصيد افتتاحي', 'transaction_type_id' => 1, 'is_protected' => true],
            ['name' => 'رأس المال', 'transaction_type_id' => 1, 'is_protected' => true],
        ]);

        // تأكد من إضافة القيم الافتراضية المناسبة حسب احتياجاتك    
    }

    public function down()
    {
        Schema::dropIfExists('transaction_statuses');
    }
}


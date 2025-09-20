<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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

        $now = Carbon::now();

        $statusIds = DB::table('transaction_statuses')->pluck('id', 'name')->all();
        $categoryIds = DB::table('categories')->pluck('id', 'name')->all();

        $pairs = [
            ['status' => 'إضافة عقد', 'category' => 'المستثمرين'],
            ['status' => 'سداد قسط', 'category' => 'المستثمرين'],
            ['status' => 'سحب سيولة', 'category' => 'المستثمرين'],
            ['status' => 'إضافة سيولة', 'category' => 'المستثمرين'],
            ['status' => 'سداد مطالبة', 'category' => 'المستثمرين'],
            ['status' => 'رأس المال', 'category' => 'المستثمرين'],
            ['status' => 'زكاة المال', 'category' => 'المستثمرين'],
            ['status' => 'شراء بضائع', 'category' => 'البضائع'],
            ['status' => 'بيع بضائع', 'category' => 'البضائع'],
            ['status' => 'إضافة عقد', 'category' => 'البضائع'],
            ['status' => 'شراء بضائع', 'category' => 'المكتب'],
            ['status' => 'بيع بضائع', 'category' => 'المكتب'],
            ['status' => 'المكاتبة', 'category' => 'المكتب'],
            ['status' => 'فرق البيع', 'category' => 'المكتب'],
            ['status' => 'إيداع حسابات', 'category' => 'المكتب'],
            ['status' => 'سحب حسابات', 'category' => 'المكتب'],
            ['status' => 'تحويل بين حسابات', 'category' => 'المكتب'],
            ['status' => 'رصيد افتتاحي', 'category' => 'المكتب'],
            ['status' => 'محاماة مطالبة', 'category' => 'المكتب'],
        ];

        $records = [];

        foreach ($pairs as $pair) {
            $statusId = $statusIds[$pair['status']] ?? null;
            $categoryId = $categoryIds[$pair['category']] ?? null;

            if (!$statusId || !$categoryId) {
                continue;
            }

            $records[] = [
                'transaction_status_id' => $statusId,
                'category_id' => $categoryId,
                'is_protected' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($records)) {
            DB::table('category_transaction_status')->insert($records);
        }
    }

    public function down()
    {
        Schema::dropIfExists('category_transaction_status');
    }
}

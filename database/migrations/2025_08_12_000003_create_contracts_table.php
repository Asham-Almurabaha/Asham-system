<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractsTable extends Migration
{
    public function up()
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('contract_number')->unique();
            
            $table->foreignId('customer_id')->constrained('customers')->onDelete('restrict'); // العميل
            $table->foreignId('guarantor_id')->nullable()->constrained('guarantors')->onDelete('restrict'); // الكفيل

            $table->foreignId('contract_status_id')->nullable()->constrained('contract_statuses')->onDelete('restrict'); // حالة العقد
            
            $table->foreignId('product_type_id')->constrained('product_types')->onDelete('restrict'); // نوع البضائع
            $table->integer('products_count')->default(0); // عدد البضائع
            $table->decimal('purchase_price', 15, 2); // سعر شراء البضائع
            $table->decimal('sale_price', 15, 2); // سعر البيع للمستثمر
            
            $table->decimal('contract_value', 15, 2); // قيمة العقد
            $table->decimal('investor_profit', 15, 2); // ربح المستثمر من العقد
            $table->decimal('total_value', 15, 2); // إجمالي قيمة العقد
            $table->decimal('discount_amount', 15, 2)->default(0); // مبلغ الخصم على العقد
            
            $table->foreignId('installment_type_id')->constrained('installment_types')->onDelete('restrict'); // نوع القسط
            $table->decimal('installment_value', 15, 2); // قيمة القسط
            $table->integer('installments_count'); // عدد الأقساط
            
            $table->date('start_date'); // تاريخ كتابة العقد
            $table->date('first_installment_date')->nullable(); // تاريخ أول قسط
            
            $table->string('contract_image')->nullable(); // صورة العقد
            $table->string('contract_customer_image')->nullable(); // صورة سند الأمر للعقد
            $table->string('contract_guarantor_image')->nullable(); // صورة سند الأمر للكفيل


            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('contracts');
    }
}

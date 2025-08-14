<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInstallmentPaymentsTable extends Migration
{
    public function up()
    {
        Schema::create('installment_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_installment_id')->constrained('contract_installments')->onDelete('cascade');
            $table->decimal('payment_amount', 10, 2)->default(0);// المبلغ المدفوع
            $table->date('payment_date');     // تاريخ الدفع
            $table->text('notes')->nullable(); // ملاحظات (اختياري)

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('installment_payments');
    }
}

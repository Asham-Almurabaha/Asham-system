<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractInstallmentsTable extends Migration
{
    public function up()
    {
        Schema::create('contract_installments', function (Blueprint $table) {
            
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade');
            $table->unsignedInteger('installment_number');  // رقم القسط (مثلاً 1، 2، 3 ...)
            $table->date('due_date');   // تاريخ استحقاق القسط
            $table->decimal('due_amount', 15, 2);   // مبلغ القسط
            $table->date('payment_date')->nullable();     // تاريخ دفع القسط (إذا تم دفعه)
            $table->decimal('payment_amount', 15, 2)->nullable();   //  مبلغ القسط المدفوع
            $table->foreignId('installment_status_id')->nullable()->constrained('installment_statuses')->nullOnDelete();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['contract_id', 'installment_number']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('installments');
    }
}

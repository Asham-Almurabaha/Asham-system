<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvestorTransactionsTable   extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('investor_transactions', function (Blueprint $table) {
            $table->id();

            // ربط بالمستثمر
            $table->foreignId('investor_id')->constrained('investors')->onDelete('cascade');

            // ربط بالعقد (اختياري)
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->onDelete('set null');

            // ربط بالعقد (القسط)
            $table->foreignId('installment_id')->nullable()->constrained('contract_installments')->onDelete('set null');

            // ربط بالحالة
            $table->foreignId('status_id')->constrained('transaction_statuses')->onDelete('restrict');

            // المبلغ
            $table->decimal('amount', 15, 2);

            // تاريخ العملية
            $table->date('transaction_date');

            // ملاحظات
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('investor_transactions');
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->id();

            // تاريخ القيد
            $table->date('entry_date');

            // ربط بالمستثمر (اختياري) — لو القيد يخص مستثمر بعينه
            $table->foreignId('investor_id')->nullable()->constrained('investors')->nullOnDelete();

            // فلاغ بسيط يحدد إن القيد يخص "المكتب" (لو مش يخص مستثمر)
            $table->boolean('is_office')->default(false)->index();

            // الحالة + النوع (للسرعة حتى لو نقدر نستنتج النوع من الحالة)
            $table->foreignId('transaction_status_id')->constrained('transaction_statuses')->cascadeOnDelete();
            $table->foreignId('transaction_type_id')->constrained('transaction_types')->cascadeOnDelete();

            // الربط بالحسابات البنكية أو الخزنة (أحدهما غالبًا يكون NULL)
            $table->foreignId('bank_account_id')->nullable()->constrained('bank_accounts')->nullOnDelete();
            $table->foreignId('safe_id')->nullable()->constrained('safes')->nullOnDelete();

            // ربط اختياري بالعقد/القسط
            $table->foreignId('contract_id')->nullable()->constrained('contracts')->nullOnDelete();
            $table->foreignId('installment_id')->nullable()->constrained('contract_installments')->nullOnDelete();

            // المبلغ + اتجاه الحركة
            $table->decimal('amount', 15, 2);
            $table->enum('direction', ['in', 'out'])->index(); // in = داخل، out = خارج

            // مرجع/ملاحظات
            $table->string('ref')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // فهارس مفيدة
            $table->index(['entry_date', 'transaction_type_id']);
            $table->index(['investor_id', 'is_office']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger_entries');
    }
};

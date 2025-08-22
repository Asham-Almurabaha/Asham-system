<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // من قام بالعملية (إن وُجد)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // معلومات الموديل المتأثر
            $table->string('auditable_type');         // App\Models\Post
            $table->unsignedBigInteger('auditable_id');

            // نوع الحدث: created/updated/deleted/restored
            $table->string('event', 20);

            // ملخص القيم (قبل/بعد) كـ JSON
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // معلومات الطلب
            $table->string('url', 2048)->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->string('user_agent', 1024)->nullable();

            // وقت التنفيذ
            $table->timestamp('performed_at')->useCurrent();

            $table->timestamps();

            // فهارس مفيدة
            $table->index(['auditable_type','auditable_id']);
            $table->index(['event']);
            $table->index(['performed_at']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('audit_logs');
    }
};
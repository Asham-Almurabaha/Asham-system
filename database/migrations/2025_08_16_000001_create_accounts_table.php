<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');                   // اسم الحساب (مثلاً: بنك QNB - رئيسي)
            $table->enum('type', ['investor','office','bank','cash']);
            $table->unsignedBigInteger('ref_id')->nullable(); // مثلاً investor_id عند type=investor
            $table->string('currency', 10)->default('SAR');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['type','ref_id']);
        });
        // إضافة القيم الافتراضية
        DB::table('accounts')->insert([
            ['name' => 'الحساب الرئيسي', 'type' => 'office', 'ref_id' => null, 'currency' => 'SAR', 'opening_balance' => 0, 'is_active' => true],
            ['name' => 'حساب بنك QNB', 'type' => 'bank', 'ref_id' => null, 'currency' => 'SAR', 'opening_balance' => 0, 'is_active' => true],
            ['name' => 'حساب خزنة المكتب', 'type' => 'cash', 'ref_id' => null, 'currency' => 'SAR', 'opening_balance' => 0, 'is_active' => true],
            ['name' => 'حساب مستثمر 1', 'type' => 'investor', 'ref_id' => 1, 'currency' => 'SAR', 'opening_balance' => 0, 'is_active' => true],
            ['name' => 'حساب مستثمر 2', 'type' => 'investor', 'ref_id' => 2, 'currency' => 'SAR', 'opening_balance' => 0, 'is_active' => true],
            ['name' => 'حساب مستثمر 3', 'type' => 'investor', 'ref_id' => 3, 'currency' => 'SAR', 'opening_balance' => 0, 'is_active' => true],
            ['name' => 'حساب مستثمر 4', 'type' => 'investor', 'ref_id' => 4, 'currency' => 'SAR', 'opening_balance' => 0, 'is_active' => true],
        ]);
    }

    public function down(): void {
        Schema::dropIfExists('accounts');
    } 
};
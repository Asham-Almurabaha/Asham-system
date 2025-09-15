<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractInvestorTable extends Migration
{
    public function up()
    {
        Schema::create('contract_investor', function (Blueprint $table) {
            $table->id();

            $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade');
            $table->foreignId('investor_id')->constrained('investors')->onDelete('cascade');

            $table->decimal('share_percentage', 5, 2)->default(0); // نسبة مشاركة المستثمر في العقد
            $table->decimal('share_value', 15, 2)->default(0); // قيمة مشاركة المستثمر في العقد

            $table->timestamps();

            $table->unique(['contract_id', 'investor_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('contract_investor');
    }
}

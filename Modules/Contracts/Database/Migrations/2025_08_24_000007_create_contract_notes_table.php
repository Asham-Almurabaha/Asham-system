<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractNotesTable extends Migration
{
    public function up(): void
    {
        Schema::create('contract_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained('contracts')->cascadeOnDelete();
            $table->date('note_date');
            $table->text('note');
            $table->timestamps();

            $table->index(['contract_id', 'note_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_notes');
    }
}

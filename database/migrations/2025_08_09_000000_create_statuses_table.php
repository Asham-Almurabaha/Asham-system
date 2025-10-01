<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('statuses', function (Blueprint $table) {
            $table->id();
            $table->string('domain');
            $table->string('name');
            $table->boolean('is_protected')->default(false);
            $table->timestamps();

            $table->unique(['domain', 'name']);
            $table->index('domain');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('statuses');
    }
};

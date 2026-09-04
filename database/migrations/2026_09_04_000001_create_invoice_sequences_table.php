<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('type')->default('prescription');
            $table->string('date_key', 8);
            $table->unsignedBigInteger('current_number')->default(0);
            $table->timestamps();

            $table->unique(['type', 'date_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_sequences');
    }
};

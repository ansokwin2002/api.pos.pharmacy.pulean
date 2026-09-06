<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_drawers', function (Blueprint $table) {
            $table->id();
            $table->date('session_date')->index();
            $table->decimal('opening_float', 12, 2)->default(0);
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->decimal('expected', 12, 2)->nullable();
            $table->decimal('counted', 12, 2)->nullable();
            $table->decimal('difference', 12, 2)->nullable();
            $table->timestamps();
        });

        Schema::create('cash_drawer_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_drawer_id')->constrained()->onDelete('cascade');
            $table->string('type', 30)->default('cash_sale');
            $table->string('label')->nullable();
            $table->string('invoice')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->decimal('change', 12, 2)->default(0);
            $table->string('client_id', 64)->nullable()->index();
            $table->timestamp('occurred_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_drawer_movements');
        Schema::dropIfExists('cash_drawers');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Changes quantity_in_boxes from integer to decimal(10,4)
     * so that partial box quantities (from tablet/strip deductions) are stored accurately.
     */
    public function up(): void
    {
        Schema::table('drugs', function (Blueprint $table) {
            $table->decimal('quantity_in_boxes', 10, 4)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drugs', function (Blueprint $table) {
            $table->integer('quantity_in_boxes')->nullable()->change();
        });
    }
};

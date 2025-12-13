<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('drugs', function (Blueprint $table) {
            // Drop existing quantity columns
            $table->dropColumn(['box', 'strip', 'tablet']);

            // Add new quantity columns
            $table->integer('strips_per_box')->nullable()->after('tablet_cost_price');
            $table->integer('tablets_per_strip')->nullable()->after('strips_per_box');
            $table->integer('quantity_in_boxes')->nullable()->after('tablets_per_strip');
            $table->integer('total_strips')->nullable()->after('quantity_in_boxes');
            $table->integer('total_tablets')->nullable()->after('total_strips');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drugs', function (Blueprint $table) {
            // Drop new quantity columns
            $table->dropColumn(['strips_per_box', 'tablets_per_strip', 'quantity_in_boxes', 'total_strips', 'total_tablets']);

            // Add back old quantity columns
            $table->integer('box')->nullable()->after('tablet_cost_price');
            $table->integer('strip')->nullable()->after('box');
            $table->integer('tablet')->nullable()->after('strip');
        });
    }
};

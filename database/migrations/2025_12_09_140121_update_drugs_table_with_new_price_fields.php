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
            $table->dropColumn(['unit', 'price', 'cost_price']);
            $table->integer('box')->nullable()->after('image');
            $table->decimal('box_price', 8, 2)->nullable()->after('box');
            $table->decimal('box_cost_price', 8, 2)->nullable()->after('box_price');
            $table->integer('strip')->nullable()->after('box_cost_price');
            $table->decimal('strip_price', 8, 2)->nullable()->after('strip');
            $table->decimal('strip_cost_price', 8, 2)->nullable()->after('strip_price');
            $table->integer('tablet')->nullable()->after('strip_cost_price');
            $table->decimal('tablet_price', 8, 2)->nullable()->after('tablet');
            $table->decimal('tablet_cost_price', 8, 2)->nullable()->after('tablet_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drugs', function (Blueprint $table) {
            $table->dropColumn([
                'box',
                'box_price',
                'box_cost_price',
                'strip',
                'strip_price',
                'strip_cost_price',
                'tablet',
                'tablet_price',
                'tablet_cost_price',
            ]);
            $table->string('unit')->nullable()->after('image');
            $table->decimal('price', 8, 2)->nullable()->after('unit');
            $table->decimal('cost_price', 8, 2)->nullable()->after('price');
        });
    }
};

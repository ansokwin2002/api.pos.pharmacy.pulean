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
            $table->dropColumn(['manufacturer', 'dosage', 'instructions', 'side_effects']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('drugs', function (Blueprint $table) {
            $table->string('manufacturer')->nullable()->after('barcode');
            $table->string('dosage', 100)->nullable()->after('manufacturer');
            $table->text('instructions')->nullable()->after('dosage');
            $table->text('side_effects')->nullable()->after('instructions');
        });
    }
};

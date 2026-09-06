<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_drawer_movements', function (Blueprint $table) {
            $table->string('user')->nullable()->after('client_id');
        });
    }

    public function down(): void
    {
        Schema::table('cash_drawer_movements', function (Blueprint $table) {
            $table->dropColumn('user');
        });
    }
};
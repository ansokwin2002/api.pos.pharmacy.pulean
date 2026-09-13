<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->decimal('doctor_fee', 10, 2)->default(0)->after('tax_rate');
        });

        DB::table('system_settings')
            ->where('doctor_fee', 0)
            ->update(['doctor_fee' => 0]);
    }

    public function down(): void
    {
        Schema::table('system_settings', function (Blueprint $table) {
            $table->dropColumn('doctor_fee');
        });
    }
};

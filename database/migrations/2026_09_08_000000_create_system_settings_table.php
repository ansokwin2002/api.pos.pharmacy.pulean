<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('login_logo')->nullable();
            $table->string('system_logo')->nullable();
            $table->boolean('recharge_audit')->default(false);
            $table->decimal('tax_rate', 5, 2)->default(10);
            $table->timestamps();
        });

        DB::table('system_settings')->insert([
            'login_logo' => null,
            'system_logo' => null,
            'recharge_audit' => false,
            'tax_rate' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};

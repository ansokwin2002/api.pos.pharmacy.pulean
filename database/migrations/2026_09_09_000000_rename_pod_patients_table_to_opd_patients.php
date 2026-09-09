<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('pod_patients') && !Schema::hasTable('opd_patients')) {
            Schema::rename('pod_patients', 'opd_patients');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('opd_patients') && !Schema::hasTable('pod_patients')) {
            Schema::rename('opd_patients', 'pod_patients');
        }
    }
};
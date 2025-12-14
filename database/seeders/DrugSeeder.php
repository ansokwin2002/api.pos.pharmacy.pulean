<?php

namespace Database\Seeders;

use App\Models\Drug;
use App\Models\Brand;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DrugSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Truncate tables to start fresh, handling foreign key constraints
        Schema::disableForeignKeyConstraints();
        Drug::truncate();
        Brand::truncate();
        Schema::enableForeignKeyConstraints();

        // Create 100 random drugs using the factory
        Drug::factory()->count(100)->create();
    }
}

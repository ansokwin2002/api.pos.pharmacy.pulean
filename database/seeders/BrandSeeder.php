<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BrandSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create the example brand from the user's request
        Brand::create([
            'name' => 'Pfizer',
            'slug' => 'pfizer',
            'description' => 'Leading global biopharmaceutical company.',
            'logo' => 'uploads/brands/pfizer.png',
            'country' => 'USA',
            'status' => 'active'
        ]);

        // Create additional sample brands
        Brand::create([
            'name' => 'GSK',
            'slug' => 'gsk',
            'description' => 'Global healthcare company focused on pharmaceuticals and vaccines.',
            'logo' => 'uploads/brands/gsk.png',
            'country' => 'UK',
            'status' => 'active'
        ]);

        Brand::create([
            'name' => 'Johnson & Johnson',
            'slug' => 'johnson-johnson',
            'description' => 'American multinational corporation developing medical devices, pharmaceuticals.',
            'logo' => 'uploads/brands/jnj.png',
            'country' => 'USA',
            'status' => 'active'
        ]);

        Brand::create([
            'name' => 'Novartis',
            'slug' => 'novartis',
            'description' => 'Swiss multinational pharmaceutical corporation.',
            'logo' => 'uploads/brands/novartis.png',
            'country' => 'Switzerland',
            'status' => 'active'
        ]);

        Brand::create([
            'name' => 'Roche',
            'slug' => 'roche',
            'description' => 'Swiss multinational healthcare company.',
            'logo' => 'uploads/brands/roche.png',
            'country' => 'Switzerland',
            'status' => 'active'
        ]);

        // Create some random brands using the factory
        Brand::factory()->count(10)->active()->create();
        Brand::factory()->count(2)->inactive()->create();
    }
}

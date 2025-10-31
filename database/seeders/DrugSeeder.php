<?php

namespace Database\Seeders;

use App\Models\Drug;
use App\Models\Brand;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DrugSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get brand IDs for relationships
        $gskBrand = Brand::where('slug', 'gsk')->first();
        $pfizerBrand = Brand::where('slug', 'pfizer')->first();

        // Create the example drug from the user's request
        Drug::create([
            'name' => 'Paracetamol 500mg',
            'slug' => 'paracetamol-500mg',
            'generic_name' => 'Paracetamol',
            'brand_name' => 'Panadol',
            'brand_id' => $gskBrand?->id,
            'category_id' => 3,
            'image' => 'uploads/drugs/paracetamol.png',
            'unit' => 'tablet',
            'price' => 0.25,
            'cost_price' => 0.15,
            'quantity' => 1500,
            'expiry_date' => '2026-07-15',
            'barcode' => '1234567890123',
            'manufacturer' => 'GSK',
            'dosage' => '500mg',
            'instructions' => 'Take 1 tablet every 6 hours after meals',
            'side_effects' => 'Nausea, dizziness',
            'status' => 'active'
        ]);

        // Create additional sample drugs
        Drug::create([
            'name' => 'Ibuprofen 200mg',
            'slug' => 'ibuprofen-200mg',
            'generic_name' => 'Ibuprofen',
            'brand_name' => 'Advil',
            'brand_id' => $pfizerBrand?->id,
            'category_id' => 3,
            'image' => 'uploads/drugs/ibuprofen.png',
            'unit' => 'tablet',
            'price' => 0.30,
            'cost_price' => 0.18,
            'quantity' => 800,
            'expiry_date' => '2025-12-31',
            'barcode' => '2345678901234',
            'manufacturer' => 'Pfizer',
            'dosage' => '200mg',
            'instructions' => 'Take 1-2 tablets every 4-6 hours as needed',
            'side_effects' => 'Stomach upset, drowsiness',
            'status' => 'active'
        ]);

        Drug::create([
            'name' => 'Amoxicillin 250mg',
            'slug' => 'amoxicillin-250mg',
            'generic_name' => 'Amoxicillin',
            'brand_name' => 'Amoxil',
            'brand_id' => $gskBrand?->id,
            'category_id' => 1,
            'image' => 'uploads/drugs/amoxicillin.png',
            'unit' => 'capsule',
            'price' => 0.45,
            'cost_price' => 0.28,
            'quantity' => 500,
            'expiry_date' => '2025-09-30',
            'barcode' => '3456789012345',
            'manufacturer' => 'GSK',
            'dosage' => '250mg',
            'instructions' => 'Take 1 capsule 3 times daily for 7-10 days',
            'side_effects' => 'Diarrhea, nausea, skin rash',
            'status' => 'active'
        ]);

        // Create some random drugs using the factory
        Drug::factory()->count(20)->create();
        
        // Create some specific scenarios
        Drug::factory()->count(5)->expiringSoon()->create();
        Drug::factory()->count(3)->outOfStock()->create();
        Drug::factory()->count(2)->inactive()->create();
    }
}

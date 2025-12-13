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
            'box_price' => 2.50,
            'box_cost_price' => 1.50,
            'strip_price' => 0.25,
            'strip_cost_price' => 0.15,
            'tablet_price' => 0.05,
            'tablet_cost_price' => 0.03,
            'strips_per_box' => 10,
            'tablets_per_strip' => 12,
            'quantity_in_boxes' => 10, // Assuming 10 boxes from old 'box' value
            'quantity' => 1500,
            'expiry_date' => '2026-07-15',
            'barcode' => '1234567890123',
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
            'box_price' => 3.00,
            'box_cost_price' => 1.80,
            'strip_price' => 0.30,
            'strip_cost_price' => 0.18,
            'tablet_price' => 0.06,
            'tablet_cost_price' => 0.036,
            'strips_per_box' => 10,
            'tablets_per_strip' => 12,
            'quantity_in_boxes' => 10, // Assuming 10 boxes from old 'box' value
            'quantity' => 800,
            'expiry_date' => '2025-12-31',
            'barcode' => '2345678901234',
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
            'box_price' => 4.50,
            'box_cost_price' => 2.80,
            'strip_price' => 0.45,
            'strip_cost_price' => 0.28,
            'tablet_price' => 0.09,
            'tablet_cost_price' => 0.056,
            'strips_per_box' => 10,
            'tablets_per_strip' => 12,
            'quantity_in_boxes' => 10, // Assuming 10 boxes from old 'box' value
            'quantity' => 500,
            'expiry_date' => '2025-09-30',
            'barcode' => '3456789012345',
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

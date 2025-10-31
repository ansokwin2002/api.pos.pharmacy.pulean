<?php

namespace Tests\Feature;

use App\Models\Drug;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class DrugApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_can_create_drug()
    {
        $drugData = [
            'name' => 'Paracetamol 500mg',
            'slug' => 'paracetamol-500mg',
            'generic_name' => 'Paracetamol',
            'brand_name' => 'Panadol',
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
        ];

        $response = $this->postJson('/api/drugs', $drugData);

        $response->assertStatus(201)
                ->assertJson($drugData);

        $this->assertDatabaseHas('drugs', $drugData);
    }

    public function test_can_list_drugs()
    {
        Drug::factory()->count(3)->create();

        $response = $this->getJson('/api/drugs');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'slug',
                            'generic_name',
                            'brand_name',
                            'category_id',
                            'image',
                            'unit',
                            'price',
                            'cost_price',
                            'quantity',
                            'expiry_date',
                            'barcode',
                            'manufacturer',
                            'dosage',
                            'instructions',
                            'side_effects',
                            'status',
                            'created_at',
                            'updated_at'
                        ]
                    ],
                    'current_page',
                    'per_page',
                    'total'
                ]);
    }

    public function test_can_show_single_drug()
    {
        $drug = Drug::factory()->create();

        $response = $this->getJson("/api/drugs/{$drug->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'id' => $drug->id,
                    'name' => $drug->name,
                    'slug' => $drug->slug,
                    'generic_name' => $drug->generic_name,
                ]);
    }

    public function test_can_update_drug()
    {
        $drug = Drug::factory()->create();
        $updateData = [
            'name' => 'Updated Drug Name',
            'price' => 1.50
        ];

        $response = $this->putJson("/api/drugs/{$drug->id}", $updateData);

        $response->assertStatus(200)
                ->assertJson($updateData);

        $this->assertDatabaseHas('drugs', array_merge(['id' => $drug->id], $updateData));
    }

    public function test_can_delete_drug()
    {
        $drug = Drug::factory()->create();

        $response = $this->deleteJson("/api/drugs/{$drug->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('drugs', ['id' => $drug->id]);
    }

    public function test_can_search_drugs()
    {
        Drug::factory()->create(['name' => 'Paracetamol 500mg']);
        Drug::factory()->create(['name' => 'Ibuprofen 200mg']);

        $response = $this->getJson('/api/drugs?search=Paracetamol');

        $response->assertStatus(200);
        $this->assertEquals(1, $response->json('data.0.name') ? 1 : 0);
    }

    public function test_validates_required_fields()
    {
        $response = $this->postJson('/api/drugs', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name', 'generic_name', 'unit', 'price', 'cost_price', 'quantity', 'expiry_date']);
    }
}

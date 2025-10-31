<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Drug;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class BrandApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    public function test_can_create_brand()
    {
        $brandData = [
            'name' => 'Pfizer',
            'slug' => 'pfizer',
            'description' => 'Leading global biopharmaceutical company.',
            'logo' => 'uploads/brands/pfizer.png',
            'country' => 'USA',
            'status' => 'active'
        ];

        $response = $this->postJson('/api/brands', $brandData);

        $response->assertStatus(201)
                ->assertJson($brandData);

        $this->assertDatabaseHas('brands', $brandData);
    }

    public function test_can_list_brands()
    {
        Brand::factory()->count(3)->create();

        $response = $this->getJson('/api/brands');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'slug',
                            'description',
                            'logo',
                            'country',
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

    public function test_can_show_single_brand()
    {
        $brand = Brand::factory()->create();

        $response = $this->getJson("/api/brands/{$brand->id}");

        $response->assertStatus(200)
                ->assertJson([
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'slug' => $brand->slug,
                ]);
    }

    public function test_can_show_brand_with_drugs()
    {
        $brand = Brand::factory()->create();
        Drug::factory()->count(2)->create(['brand_id' => $brand->id]);

        $response = $this->getJson("/api/brands/{$brand->id}");

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'id',
                    'name',
                    'drugs' => [
                        '*' => [
                            'id',
                            'name',
                            'brand_id'
                        ]
                    ]
                ]);
    }

    public function test_can_update_brand()
    {
        $brand = Brand::factory()->create();
        $updateData = [
            'name' => 'Updated Brand Name',
            'country' => 'Canada'
        ];

        $response = $this->putJson("/api/brands/{$brand->id}", $updateData);

        $response->assertStatus(200)
                ->assertJson($updateData);

        $this->assertDatabaseHas('brands', array_merge(['id' => $brand->id], $updateData));
    }

    public function test_can_delete_brand()
    {
        $brand = Brand::factory()->create();

        $response = $this->deleteJson("/api/brands/{$brand->id}");

        $response->assertStatus(204);
        $this->assertDatabaseMissing('brands', ['id' => $brand->id]);
    }

    public function test_can_search_brands()
    {
        Brand::factory()->create(['name' => 'Pfizer Inc']);
        Brand::factory()->create(['name' => 'GSK Limited']);

        $response = $this->getJson('/api/brands?search=Pfizer');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertStringContainsString('Pfizer', $data[0]['name']);
    }

    public function test_can_filter_brands_by_status()
    {
        Brand::factory()->create(['status' => 'active']);
        Brand::factory()->create(['status' => 'inactive']);

        $response = $this->getJson('/api/brands?status=active');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('active', $data[0]['status']);
    }

    public function test_validates_required_fields()
    {
        $response = $this->postJson('/api/brands', []);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['name']);
    }

    public function test_validates_unique_slug()
    {
        Brand::factory()->create(['slug' => 'pfizer']);

        $response = $this->postJson('/api/brands', [
            'name' => 'Pfizer',
            'slug' => 'pfizer'
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['slug']);
    }
}

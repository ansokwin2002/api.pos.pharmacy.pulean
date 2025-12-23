<?php

namespace Tests\Feature;

use App\Models\Drug;
use App\Models\Brand;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
// Removed use Illuminate\Support\Facades\Artisan;

class StockDeductionApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a default brand and company for drug creation
        Brand::factory()->create();
        Company::factory()->create();
    }

    public function test_can_deduct_drug_stock_in_tablets()
    {
        $drug = Drug::factory()->create([
            'quantity_in_boxes' => 1,
            'strips_per_box' => 10,
            'tablets_per_strip' => 10,
            'total_tablets' => 100, // Should be calculated by boot method, but setting for clarity
        ]);

        $payload = [
            'deductions' => [
                [
                    'drug_id' => $drug->id,
                    'deducted_quantity' => 10,
                    'deduction_unit' => 'tablet',
                ],
            ],
        ];

        $response = $this->patchJson('/api/drugs/deduct-stock', $payload);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'drug_id' => $drug->id,
                     'message' => 'Stock deducted successfully',
                     'new_total_tablets' => 90,
                 ]);

        $this->assertDatabaseHas('drugs', [
            'id' => $drug->id,
            'total_tablets' => 90,
            'quantity_in_boxes' => 0, // 90 tablets / (10 strips/box * 10 tablets/strip) = 0.9 boxes, floored to 0
        ]);
    }

    public function test_can_deduct_drug_stock_in_strips()
    {
        $drug = Drug::factory()->create([
            'quantity_in_boxes' => 2,
            'strips_per_box' => 10,
            'tablets_per_strip' => 10,
            'total_tablets' => 200, // 2 boxes * 10 strips/box * 10 tablets/strip
        ]);

        $payload = [
            'deductions' => [
                [
                    'drug_id' => $drug->id,
                    'deducted_quantity' => 5, // 5 strips
                    'deduction_unit' => 'strip',
                ],
            ],
        ];

        $response = $this->patchJson('/api/drugs/deduct-stock', $payload);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'drug_id' => $drug->id,
                     'message' => 'Stock deducted successfully',
                     'new_total_tablets' => 150, // 200 - (5 strips * 10 tablets/strip) = 150
                 ]);

        $this->assertDatabaseHas('drugs', [
            'id' => $drug->id,
            'total_tablets' => 150,
            'quantity_in_boxes' => 1, // 150 tablets / 100 tablets/box = 1.5 boxes, floored to 1
        ]);
    }

    public function test_can_deduct_drug_stock_in_boxes()
    {
        $drug = Drug::factory()->create([
            'quantity_in_boxes' => 3,
            'strips_per_box' => 10,
            'tablets_per_strip' => 10,
            'total_tablets' => 300, // 3 boxes * 10 strips/box * 10 tablets/strip
        ]);

        $payload = [
            'deductions' => [
                [
                    'drug_id' => $drug->id,
                    'deducted_quantity' => 1, // 1 box
                    'deduction_unit' => 'box',
                ],
            ],
        ];

        $response = $this->patchJson('/api/drugs/deduct-stock', $payload);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'drug_id' => $drug->id,
                     'message' => 'Stock deducted successfully',
                     'new_total_tablets' => 200, // 300 - (1 box * 10 strips/box * 10 tablets/strip) = 200
                 ]);

        $this->assertDatabaseHas('drugs', [
            'id' => $drug->id,
            'total_tablets' => 200,
            'quantity_in_boxes' => 2, // 200 tablets / 100 tablets/box = 2 boxes
        ]);
    }

    public function test_cannot_deduct_drug_stock_with_insufficient_stock()
    {
        $drug = Drug::factory()->create([
            'quantity_in_boxes' => 0,
            'strips_per_box' => 1,
            'tablets_per_strip' => 1,
            'total_tablets' => 5, // Only 5 tablets
        ]);

        $payload = [
            'deductions' => [
                [
                    'drug_id' => $drug->id,
                    'deducted_quantity' => 10, // Requesting 10 tablets
                    'deduction_unit' => 'tablet',
                ],
            ],
        ];

        $response = $this->patchJson('/api/drugs/deduct-stock', $payload);

        $response->assertStatus(400)
                 ->assertJsonFragment([
                     'message' => 'Some deductions failed due to insufficient stock',
                 ])
                 ->assertJsonFragment([
                     'drug_id' => $drug->id,
                     'message' => 'Insufficient stock for drug: ' . $drug->name,
                     'available_tablets' => 5,
                     'requested_deduction_in_tablets' => 10,
                 ]);

        // Assert stock remains unchanged
        $this->assertDatabaseHas('drugs', [
            'id' => $drug->id,
            'total_tablets' => 5,
        ]);
    }

    public function test_can_deduct_multiple_drug_stocks()
    {
        $drug1 = Drug::factory()->create([
            'quantity_in_boxes' => 1, 'strips_per_box' => 10, 'tablets_per_strip' => 10, 'total_tablets' => 100,
        ]); // 100 tablets
        $drug2 = Drug::factory()->create([
            'quantity_in_boxes' => 2, 'strips_per_box' => 5, 'tablets_per_strip' => 5, 'total_tablets' => 50,
        ]); // 50 tablets (2*5*5)

        $payload = [
            'deductions' => [
                [
                    'drug_id' => $drug1->id,
                    'deducted_quantity' => 20,
                    'deduction_unit' => 'tablet',
                ],
                [
                    'drug_id' => $drug2->id,
                    'deducted_quantity' => 2, // 2 strips
                    'deduction_unit' => 'strip',
                ],
            ],
        ];

        $response = $this->patchJson('/api/drugs/deduct-stock', $payload);

        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'All stock deductions processed successfully'])
                 ->assertJsonFragment(['drug_id' => $drug1->id, 'new_total_tablets' => 80])
                 ->assertJsonFragment(['drug_id' => $drug2->id, 'new_total_tablets' => 40]); // 50 - (2*5) = 40

        $this->assertDatabaseHas('drugs', ['id' => $drug1->id, 'total_tablets' => 80]);
        $this->assertDatabaseHas('drugs', ['id' => $drug2->id, 'total_tablets' => 40]);
    }

    public function test_rolls_back_on_any_insufficient_stock_in_batch()
    {
        $drug1 = Drug::factory()->create([
            'quantity_in_boxes' => 1, 'strips_per_box' => 10, 'tablets_per_strip' => 10, 'total_tablets' => 100,
        ]); // Sufficient stock
        $drug2 = Drug::factory()->create([
            'quantity_in_boxes' => 0, 'strips_per_box' => 1, 'tablets_per_strip' => 1, 'total_tablets' => 5,
        ]); // Insufficient stock

        $payload = [
            'deductions' => [
                [
                    'drug_id' => $drug1->id,
                    'deducted_quantity' => 10,
                    'deduction_unit' => 'tablet',
                ],
                [
                    'drug_id' => $drug2->id,
                    'deducted_quantity' => 10, // Exceeds available 5 tablets
                    'deduction_unit' => 'tablet',
                ],
            ],
        ];

        $response = $this->patchJson('/api/drugs/deduct-stock', $payload);

        $response->assertStatus(400)
                 ->assertJsonFragment(['message' => 'Some deductions failed due to insufficient stock']);

        // Assert that both drug stocks remain unchanged due to rollback
        $this->assertDatabaseHas('drugs', ['id' => $drug1->id, 'total_tablets' => 100]);
        $this->assertDatabaseHas('drugs', ['id' => $drug2->id, 'total_tablets' => 5]);
    }

    public function test_validates_deduction_request()
    {
        // Test missing deductions array
        $response = $this->patchJson('/api/drugs/deduct-stock', []);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['deductions']);

        // Test invalid drug_id
        $response = $this->patchJson('/api/drugs/deduct-stock', [
            'deductions' => [
                [
                    'drug_id' => 9999, // Non-existent ID
                    'deducted_quantity' => 1,
                    'deduction_unit' => 'tablet',
                ],
            ],
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['deductions.0.drug_id']);

        // Test negative deducted_quantity
        $drug = Drug::factory()->create();
        $response = $this->patchJson('/api/drugs/deduct-stock', [
            'deductions' => [
                [
                    'drug_id' => $drug->id,
                    'deducted_quantity' => -5,
                    'deduction_unit' => 'tablet',
                ],
            ],
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['deductions.0.deducted_quantity']);

        // Test invalid deduction_unit
        $response = $this->patchJson('/api/drugs/deduct-stock', [
            'deductions' => [
                [
                    'drug_id' => $drug->id,
                    'deducted_quantity' => 1,
                    'deduction_unit' => 'invalid_unit',
                ],
            ],
        ]);
        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['deductions.0.deduction_unit']);
    }
}
<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\PatientHistory;
use Illuminate\Support\Facades\DB;

class PatientHistoryApiTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** @test */
    public function it_can_create_a_patient_history()
    {
        $data = [
            'type' => $this->faker->word,
            'json_data' => json_encode(['key' => 'value']),
        ];

        $response = $this->postJson('/api/patient-histories', $data);

        $response->assertStatus(201)
            ->assertJsonFragment([
                'type' => $data['type'],
                'json_data' => $data['json_data'],
            ]);

        $this->assertDatabaseHas('patient_histories', [
            'type' => $data['type'],
        ]);

        $patientHistory = PatientHistory::where('type', $data['type'])->first();
        $this->assertEquals(json_decode($data['json_data']), json_decode($patientHistory->json_data));
    }

    /** @test */
    public function it_can_return_a_list_of_patient_histories()
    {
        DB::table('patient_histories')->truncate();
        PatientHistory::factory()->count(3)->create();

        $response = $this->getJson('/api/patient-histories');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }
}

<?php

namespace App\Http\Controllers;

use App\Helpers\HashidsHelper;
use App\Models\TempPrescription;
use Illuminate\Http\Request;

class TempPrescriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = TempPrescription::query();

        if ($patientId = $request->query('patient_id')) {
            $decodedId = HashidsHelper::decode($patientId) ?? $patientId;
            $query->where(function ($q) use ($patientId, $decodedId) {
                $q->where('json_data->patient_id', (string) $patientId)
                  ->orWhere('json_data->patient_id', (string) $decodedId);
            });
        }

        return response()->json($query->get());
    }


    public function getByPatientId(string $patientId)
    {
        $decodedId = HashidsHelper::decode($patientId) ?? $patientId;

        $tempPrescriptions = TempPrescription::where(function ($q) use ($patientId, $decodedId) {
            $q->where('json_data->patient_id', (string) $patientId)
              ->orWhere('json_data->patient_id', (string) $decodedId);
        })->get();

        // Always return 200 with empty array if none found (avoids triggering global 404 error handler)
        return response()->json($tempPrescriptions, 200);
    }



    /**
     * Store a newly created resource in storage.
     */
    public function storeByPatientId(Request $request, string $patientId)
    {
        $request->validate([
            'drugs' => 'required|array',
        ]);

        $decodedId = HashidsHelper::decode($patientId) ?? $patientId;

        $tempPrescription = TempPrescription::create([
            'json_data' => [
                'patient_id' => (string) $patientId,
                'drugs' => $request->drugs,
            ],
        ]);

        return response()->json($tempPrescription, 201);
    }
    
    // ...
    public function destroyByPatientId(string $patientId)
    {
        $decodedId = HashidsHelper::decode($patientId) ?? $patientId;

        $deletedCount = TempPrescription::where(function ($q) use ($patientId, $decodedId) {
            $q->where('json_data->patient_id', (string) $patientId)
              ->orWhere('json_data->patient_id', (string) $decodedId);
        })->delete();

        // Always return 200 regardless of whether records existed
        return response()->json([
            'message' => "{$deletedCount} temporary prescription(s) deleted successfully"
        ], 200);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'json_data' => 'required|array',
        ]);

        $tempPrescription = TempPrescription::create([
            'json_data' => $request->json_data,
        ]);

        return response()->json($tempPrescription, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $tempPrescription = TempPrescription::find($id);

        if (!$tempPrescription) {
            return response()->json([
                'message' => 'Temporary prescription not found'
            ], 404);
        }

        return response()->json($tempPrescription, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $tempPrescription = TempPrescription::find($id);

        if (!$tempPrescription) {
            return response()->json([
                'message' => 'Temporary prescription not found'
            ], 404);
        }

        $request->validate([
            'json_data' => 'required|array',
        ]);

        $tempPrescription->update([
            'json_data' => $request->json_data,
        ]);

        return response()->json($tempPrescription, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $tempPrescription = TempPrescription::find($id);

        if (!$tempPrescription) {
            return response()->json([
                'message' => 'Temporary prescription not found'
            ], 404);
        }

        $tempPrescription->delete();

        return response()->json([
            'message' => 'Temporary prescription deleted successfully'
        ], 200);
    }
}

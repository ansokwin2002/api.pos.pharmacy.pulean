<?php

namespace App\Http\Controllers;

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
            $query->where('json_data->patient_id', (string) $patientId);
        }

        return response()->json($query->get());
    }


    public function getByPatientId(string $patientId)
    {
        $tempPrescriptions = TempPrescription::where(
            'json_data->patient_id',
            (string) $patientId
        )->get();

        if ($tempPrescriptions->isEmpty()) {
            return response()->json([
                'message' => 'No temporary prescriptions found for the given patient ID'
            ], 404);
        }

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

        $tempPrescription = TempPrescription::create([
            'json_data' => [
                'patient_id' => (string) $patientId,
                'drugs' => $request->drugs,
            ],
        ]);

        return response()->json($tempPrescription, 201);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'json_data' => 'required|json',
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
            'json_data' => 'required|json',
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

    public function destroyByPatientId(string $patientId)
    {
        $deletedCount = TempPrescription::where(
            'json_data->patient_id',
            (string) $patientId
        )->delete();

        if ($deletedCount === 0) {
            return response()->json([
                'message' => 'No temporary prescriptions found for the given patient ID'
            ], 404);
        }

        return response()->json([
            'message' => "{$deletedCount} temporary prescription(s) deleted successfully"
        ], 200);
    }

}

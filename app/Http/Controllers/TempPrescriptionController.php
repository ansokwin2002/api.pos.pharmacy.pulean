<?php

namespace App\Http\Controllers;

use App\Models\TempPrescription;
use Illuminate\Http\Request;

class TempPrescriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $tempPrescriptions = TempPrescription::orderBy('created_at', 'desc')->get();
        return response()->json($tempPrescriptions, 200);
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
}

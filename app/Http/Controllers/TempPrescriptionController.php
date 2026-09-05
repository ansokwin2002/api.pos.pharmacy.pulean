<?php

namespace App\Http\Controllers;

use App\Helpers\HashidsHelper;
use App\Models\TempPrescription;
use App\Models\InvoiceSequence;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        $perPage = (int) $request->query('per_page', 15);
        return response()->json($query->paginate($perPage));
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
     * Complete a prescription: generate invoice number, deduct stock, and save — all in one request.
     */
    public function complete(Request $request, StockService $stockService)
    {
        $validated = $request->validate([
            'json_data' => 'required|array',
            'json_data.type' => 'required|string',
            'json_data.patient_id' => 'required|string',
            'json_data.items' => 'required|array|min:1',
            'deductions' => 'required|array|min:1',
            'deductions.*.drug_id' => 'required|integer|exists:drugs,id',
            'deductions.*.deducted_quantity' => 'required|integer|min:1',
            'deductions.*.deduction_unit' => 'required|string|in:box,strip,tablet',
        ]);

        $jsonData = $validated['json_data'];
        $deductions = $validated['deductions'];

        DB::beginTransaction();
        try {
            // 1. Generate invoice number atomically (same logic as InvoiceController)
            $dateKey = now()->format('Ymd');
            $type = $jsonData['type'] ?? 'prescription';
            $sequence = InvoiceSequence::where('type', $type)
                ->where('date_key', $dateKey)
                ->lockForUpdate()
                ->first();

            if (!$sequence) {
                $sequence = InvoiceSequence::create([
                    'type' => $type,
                    'date_key' => $dateKey,
                    'current_number' => 0,
                ]);
            }

            $sequence->increment('current_number');
            $invoiceNumber = $dateKey . '-' . str_pad($sequence->current_number, 2, '0', STR_PAD_LEFT);

            $jsonData['invoice_number'] = $invoiceNumber;
            $jsonData['stock_deducted'] = true;
            $jsonData['status'] = 'completed';

            // 2. Deduct stock (does not commit — we handle the transaction)
            $stockResult = $stockService->deduct($deductions, rollbackOnError: false);
            if (!$stockResult['success']) {
                DB::rollBack();
                return response()->json([
                    'message' => $stockResult['message'],
                    'errors' => $stockResult['errors'],
                ], 400);
            }

            // 3. Save prescription
            $prescription = TempPrescription::create([
                'json_data' => $jsonData,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Prescription completed successfully',
                'invoice_number' => $invoiceNumber,
                'data' => $prescription,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to complete prescription',
                'error' => $e->getMessage(),
            ], 500);
        }
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

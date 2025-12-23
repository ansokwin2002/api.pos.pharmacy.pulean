<?php

namespace App\Http\Controllers;

use App\Models\Drug;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class DrugController extends Controller
{
    public function index(Request $request)
    {
        $query = Drug::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('generic_name', 'like', "%{$search}%")
                  ->orWhere('brand_name', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($categoryId = $request->query('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($brandId = $request->query('brand_id')) {
            $query->where('brand_id', $brandId);
        }

        if ($request->query('in_stock') === 'true') {
            $query->inStock();
        }

        if ($request->query('expiring_soon') === 'true') {
            $days = (int) $request->query('expiring_days', 30);
            $query->expiringSoon($days);
        }

        if ($typeDrug = $request->query('type_drug')) {
            $query->where('type_drug', $typeDrug);
        }

        if ($companyId = $request->query('company_id')) { // Add company_id filter
            $query->where('company_id', $companyId);
        }

        $perPage = (int) $request->query('per_page', 15);

        return response()->json($query->paginate($perPage));
    }

    public function show(Drug $drug)
    {
        return response()->json($drug);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $drug = Drug::create($data);

        return response()->json($drug, Response::HTTP_CREATED);
    }

    public function update(Request $request, Drug $drug)
    {
        $data = $this->validateData($request, true);

        if (isset($data['name']) && !isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $drug->fill($data);
        $drug->save();

        return response()->json($drug);
    }

    public function destroy(Drug $drug)
    {
        $drug->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    public function getDetailsByTypeDrug(Request $request, Drug $drug)
    {
        $validated = $request->validate([
            'type' => 'required|string|in:box-strip-tablet,box-only',
        ]);

        $response = [];

        if ($validated['type'] === 'box-strip-tablet') {
            $response['tablet_price'] = $drug->tablet_price;
            $response['strip_price'] = $drug->strip_price;
            $response['box_price'] = $drug->box_price;
        }

        if ($validated['type'] === 'box-only') {
            $response['box_price'] = $drug->box_price;
        }

        return response()->json($response);
    }

    public function deductStock(Request $request)
    {
        // 1. Validation
        $validator = Validator::make($request->all(), [
            'deductions' => ['required', 'array', 'min:1'],
            'deductions.*.drug_id' => ['required', 'integer', 'exists:drugs,id'],
            'deductions.*.deducted_quantity' => ['required', 'integer', 'min:1'],
            'deductions.*.deduction_unit' => ['required', 'string', 'in:box,strip,tablet'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $results = [];
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($request->deductions as $deductionItem) {
                $drug = Drug::find($deductionItem['drug_id']);

                // These defaults help avoid division by zero and ensure calculations work
                $stripsPerBox = $drug->strips_per_box ?? 1;
                $tabletsPerStrip = $drug->tablets_per_strip ?? 1;

                if ($stripsPerBox === 0) $stripsPerBox = 1;
                if ($tabletsPerStrip === 0) $tabletsPerStrip = 1;

                $tabletsToDeduct = 0;

                if ($deductionItem['deduction_unit'] === 'tablet') {
                    $tabletsToDeduct = $deductionItem['deducted_quantity'];
                } elseif ($deductionItem['deduction_unit'] === 'strip') {
                    $tabletsToDeduct = $deductionItem['deducted_quantity'] * $tabletsPerStrip;
                } elseif ($deductionItem['deduction_unit'] === 'box') {
                    $tabletsToDeduct = $deductionItem['deducted_quantity'] * $stripsPerBox * $tabletsPerStrip;
                }

                // Check for sufficient stock
                if ($drug->total_tablets < $tabletsToDeduct) {
                    $errors[] = [
                        'drug_id' => $drug->id,
                        'message' => 'Insufficient stock for drug: ' . $drug->name,
                        'available_tablets' => $drug->total_tablets,
                        'requested_deduction_in_tablets' => $tabletsToDeduct,
                    ];
                    continue; // Skip to next deduction item
                }

                // Perform deduction
                $drug->total_tablets -= $tabletsToDeduct;

                // The boot method in Drug.php will now handle recalculating
                // quantity_in_boxes and total_strips based on the new total_tablets.
                $drug->save();

                $results[] = [
                    'drug_id' => $drug->id,
                    'message' => 'Stock deducted successfully',
                    'new_total_tablets' => $drug->total_tablets,
                ];
            }

            if (!empty($errors)) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Some deductions failed due to insufficient stock',
                    'failed_deductions' => $errors,
                ], Response::HTTP_BAD_REQUEST);
            }

            DB::commit();
            return response()->json([
                'message' => 'All stock deductions processed successfully',
                'results' => $results,
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'An unexpected error occurred during stock deduction',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function validateData(Request $request, bool $partial = false): array
    {
        $rules = [
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                'unique:drugs,slug' . ($partial ? ',' . optional($request->route('drug'))->id : ''),
            ],
            'generic_name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'brand_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'brand_id' => ['sometimes', 'nullable', 'integer', 'exists:brands,id'],
            'company_id' => ['sometimes', 'nullable', 'integer', 'exists:companies,id'], // Added company_id validation
            'category_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'image' => ['sometimes', 'nullable', 'string', 'max:500'],
            'box_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'box_cost_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'strip_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'strip_cost_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'tablet_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'tablet_cost_price' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'strips_per_box' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'tablets_per_strip' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'quantity_in_boxes' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'expiry_date' => [$partial ? 'sometimes' : 'required', 'date', 'after:today'],
            'barcode' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
                'unique:drugs,barcode' . ($partial ? ',' . optional($request->route('drug'))->id : ''),
            ],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
            'type_drug' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];

        if (!$partial && $request->input('type_drug') === 'box-strip-tablet') {
            $rules['strip_price'][] = 'required';
            $rules['strip_cost_price'][] = 'required';
            $rules['tablet_price'][] = 'required';
            $rules['tablet_cost_price'][] = 'required';
            $rules['strips_per_box'][] = 'required';
            $rules['tablets_per_strip'][] = 'required';
        }

        return $request->validate($rules);
    }
}

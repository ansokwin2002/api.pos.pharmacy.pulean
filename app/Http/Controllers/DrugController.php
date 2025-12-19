<?php

namespace App\Http\Controllers;

use App\Models\Drug;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

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
        }

        if ($validated['type'] === 'box-only') {
            $response['box_price'] = $drug->box_price;
        }

        return response()->json($response);
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

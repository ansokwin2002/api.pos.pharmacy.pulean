<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BrandController extends Controller
{
    public function index(Request $request)
    {
        $query = Brand::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('country', 'like', "%{$search}%");
            });
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($country = $request->query('country')) {
            $query->where('country', 'like', "%{$country}%");
        }

        if ($request->query('with_drugs_count') === 'true') {
            $query->withDrugsCount();
        }

        if ($request->query('with_active_drugs_count') === 'true') {
            $query->withActiveDrugsCount();
        }

        $perPage = (int) $request->query('per_page', 15);
        return response()->json($query->paginate($perPage));
    }

    public function show(Brand $brand)
    {
        $brand->load('drugs');
        return response()->json($brand);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        
        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        
        $brand = Brand::create($data);
        return response()->json($brand, Response::HTTP_CREATED);
    }

    public function update(Request $request, Brand $brand)
    {
        $data = $this->validateData($request, partial: true);
        
        // Generate slug if name is updated but slug is not provided
        if (isset($data['name']) && !isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }
        
        $brand->fill($data);
        $brand->save();
        return response()->json($brand);
    }

    public function destroy(Brand $brand)
    {
        $brand->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    private function validateData(Request $request, bool $partial = false): array
    {
        $rules = [
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'slug' => ['sometimes', 'string', 'max:255', 'unique:brands,slug' . ($partial ? ',' . $request->route('brand')?->id : '')],
            'description' => ['sometimes', 'nullable', 'string'],
            'logo' => ['sometimes', 'nullable', 'string', 'max:500'],
            'country' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'string', 'in:active,inactive'],
        ];
        
        return $request->validate($rules);
    }
}

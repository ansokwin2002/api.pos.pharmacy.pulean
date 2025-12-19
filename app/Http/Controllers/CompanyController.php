<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $query = Company::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->query('per_page', 15);
        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $company = Company::create($data);
        return response()->json($company, Response::HTTP_CREATED);
    }

    public function show(Company $company)
    {
        return response()->json($company);
    }

    public function update(Request $request, Company $company)
    {
        $data = $this->validateData($request, $company->id, partial: true);
        $company->fill($data);
        $company->save();
        return response()->json($company);
    }

    public function destroy(Company $company)
    {
        $company->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    private function validateData(Request $request, ?int $companyId = null, bool $partial = false): array
    {
        $rules = [
            'name' => [
                $partial ? 'sometimes' : 'required',
                'string',
                'max:255',
                'unique:companies,name' . ($companyId ? ',' . $companyId : '')
            ],
            'status' => ['sometimes', 'nullable', 'boolean'],
        ];

        return $request->validate($rules);
    }
}

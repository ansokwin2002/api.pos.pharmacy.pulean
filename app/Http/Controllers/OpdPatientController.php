<?php

namespace App\Http\Controllers;

use App\Models\OpdPatient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OpdPatientController extends Controller
{
    public function index(Request $request)
    {
        $query = OpdPatient::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('gender', 'like', "%{$search}%")
                  ->orWhere('telephone', 'like', "%{$search}%");
            });
        }

        // Always show the most recently created patients first
        $query->orderByDesc('created_at')->orderByDesc('id');

        $perPage = (int) $request->query('per_page', 15);
        return response()->json($query->paginate($perPage));
    }

    public function show(OpdPatient $opdPatient)
    {
        return response()->json($opdPatient);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $opdPatient = OpdPatient::create($data);
        return response()->json($opdPatient, Response::HTTP_CREATED);
    }

    public function update(Request $request, OpdPatient $opdPatient)
    {
        $data = $this->validateData($request, partial: true);
        $opdPatient->fill($data);
        $opdPatient->save();
        return response()->json($opdPatient);
    }

    public function destroy(OpdPatient $opdPatient)
    {
        $opdPatient->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }

    private function validateData(Request $request, bool $partial = false): array
    {
        $rules = [
            'name' => [$partial ? 'sometimes' : 'required', 'string', 'max:255'],
            'gender' => ['sometimes', 'nullable', 'string', 'max:50'],
            'age' => ['sometimes', 'nullable', 'string', 'min:0', 'max:150'],
            'telephone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address' => ['sometimes', 'nullable', 'string'],
        ];

        return $request->validate($rules);
    }
}

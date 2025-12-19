<?php

namespace App\Http\Controllers;

use App\Models\PodPatient;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class PodPatientController extends Controller
{
    public function index(Request $request)
    {
        $query = PodPatient::query();

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('gender', 'like', "%{$search}%")
                  ->orWhere('telephone', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->query('per_page', 15);
        return response()->json($query->paginate($perPage));
    }

    public function show(PodPatient $podPatient)
    {
        return response()->json($podPatient);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        $podPatient = PodPatient::create($data);
        return response()->json($podPatient, Response::HTTP_CREATED);
    }

    public function update(Request $request, PodPatient $podPatient)
    {
        $data = $this->validateData($request, partial: true);
        $podPatient->fill($data);
        $podPatient->save();
        return response()->json($podPatient);
    }

    public function destroy(PodPatient $podPatient)
    {
        $podPatient->delete();
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

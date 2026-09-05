<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::withCount('permissions')
            ->when($request->keyword, fn ($q) => $q->where(fn ($w) => $w->where('name', 'like', "%{$request->keyword}%")->orWhere('display_name', 'like', "%{$request->keyword}%")))
            ->orderBy('id')
            ->paginate($request->per_page ?: 15);

        return response()->json([
            'data' => $roles,
        ]);
    }

    public function all()
    {
        return response()->json([
            'data' => Role::orderBy('id')->get(['id', 'name', 'display_name']),
        ]);
    }

    public function show(Role $role)
    {
        return response()->json([
            'data' => $role->load('permissions'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', 'unique:roles,name'],
            'display_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $role = Role::create($validated);

        if ($request->has('permission_ids')) {
            $role->permissions()->sync(
                array_filter((array) $request->permission_ids)
            );
        }

        return response()->json([
            'message' => 'Role created successfully.',
            'data' => $role->load('permissions'),
        ], 201);
    }

    public function update(Request $request, Role $role)
    {
        if ($role->name === 'admin') {
            return response()->json(['message' => 'The admin role cannot be renamed.'], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:50', Rule::unique('roles', 'name')->ignore($role->id)],
            'display_name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $role->update($validated);

        if ($request->has('permission_ids')) {
            $role->permissions()->sync(
                array_filter((array) $request->permission_ids)
            );
        }

        return response()->json([
            'message' => 'Role updated successfully.',
            'data' => $role->load('permissions'),
        ]);
    }

    public function destroy(Role $role)
    {
        if ($role->name === 'admin') {
            return response()->json(['message' => 'The admin role cannot be deleted.'], 403);
        }

        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully.',
        ]);
    }

    /**
     * Sync the permissions granted to a role.
     */
    public function syncPermissions(Request $request, Role $role)
    {
        $request->validate([
            'permission_ids' => ['present', 'array'],
            'permission_ids.*' => ['integer', 'exists:permissions,id'],
        ]);

        $role->permissions()->sync($request->permission_ids);

        return response()->json([
            'message' => 'Permissions updated successfully.',
            'data' => $role->load('permissions'),
        ]);
    }
}
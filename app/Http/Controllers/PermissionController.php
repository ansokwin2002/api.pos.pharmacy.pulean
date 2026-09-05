<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * List all permissions grouped by module.
     */
    public function index(Request $request)
    {
        $permissions = Permission::query()
            ->when($request->module, fn ($q) => $q->where('module', $request->module))
            ->orderBy('sort')
            ->get();

        $grouped = $permissions
            ->groupBy('module')
            ->map(function ($items, $module) {
                return [
                    'module' => $module,
                    'permissions' => $items->map(fn (Permission $p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                        'display_name' => $p->display_name,
                        'sort' => $p->sort,
                    ])->values(),
                ];
            })
            ->values();

        return response()->json([
            'data' => $grouped,
        ]);
    }
}
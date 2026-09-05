<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && $user->isAdmin()) {
            return $next($request);
        }

        return response()->json([
            'message' => 'Forbidden. Admin privileges required.',
        ], 403);
    }
}
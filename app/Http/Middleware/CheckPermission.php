<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permission)
    {
        if (!Auth::guard('sanctum')->check()) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        $user = Auth::guard('sanctum')->user();

        if (!$user->hasPermissionTo($permission)) {
            return response()->json([
                'message' => 'Unauthorized. You do not have permission: ' . $permission
            ], 403);
        }

        return $next($request);
    }
}
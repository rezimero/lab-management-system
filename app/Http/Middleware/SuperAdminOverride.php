<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SuperAdminOverride
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        // Check if user is superadmin
        if ($user && ($user->role === 'superadmin' || session('is_superadmin', false))) {
            // Set the superadmin override flag
            app()->instance('superadmin.override', true);
            session(['is_superadmin' => true]);
            return $next($request);
        }

        // Check if impersonating
        if (session('impersonating', false)) {
            // Allow impersonation to proceed
            return $next($request);
        }

        // Not a superadmin - proceed normally
        return $next($request);
    }
}
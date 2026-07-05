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
        // This could be from landlord database or tenant database
        if ($user && $user->role === 'superadmin') {
            // Set the superadmin override flag
            app()->instance('superadmin.override', true);
            
            // Also set in session for controllers
            session(['is_superadmin' => true]);
            
            return $next($request);
        }

        // Check if superadmin flag is in session
        if (session('is_superadmin', false)) {
            app()->instance('superadmin.override', true);
            return $next($request);
        }

        // Not a superadmin - proceed normally
        return $next($request);
    }
}
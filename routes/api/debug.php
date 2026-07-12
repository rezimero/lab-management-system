<?php

use App\Http\Middleware\SetTenantContext;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/debug/connection', function (Request $request) {
    $tenant = Tenant::current();
    $user = $request->user();

    return response()->json([
        'tenant' => $tenant ? [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'domain' => $tenant->domain,
            'database' => $tenant->database,
        ] : null,

        'current_database' => DB::connection('tenant')
            ->getDatabaseName(),

        'user' => $user ? [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
        ] : null,

        'is_superadmin_override' => app()->bound('superadmin.override')
            ? app('superadmin.override')
            : false,
    ]);
})->middleware([
    SetTenantContext::class.':required',
    'auth:sanctum',
]);
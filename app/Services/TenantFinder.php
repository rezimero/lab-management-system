<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Multitenancy\TenantFinder\TenantFinder as BaseTenantFinder;
use Spatie\Multitenancy\Models\Tenant as SpatieTenant;

class TenantFinder extends BaseTenantFinder
{
    public function findForRequest(Request $request): ?SpatieTenant
    {
        $domain = $request->header('X-Tenant-Domain') 
            ?? $request->input('domain') 
            ?? $request->route('domain');

        Log::info("🔍 TenantFinder initiated. Extracted Domain string: '{$domain}'");

        if (!$domain) {
            Log::warning("⚠️ TenantFinder stopped: No domain string provided in request payload.");
            return null;
        }

        // Search landlord database
        $tenant = Tenant::where('domain', $domain)->first();

        if (!$tenant) {
            // Fetch all valid domains currently in the landlord table to spot typos or formatting issues
            $existingDomains = Tenant::pluck('domain')->implode(', ');
            Log::error("❌ TenantFinder FAILED: No record matches '{$domain}' in the landlord database. Existing domains are: [{$existingDomains}]");
            return null;
        }

        Log::info("🎯 TenantFinder SUCCESS: Matched domain '{$domain}' to Database: [{$tenant->database}]");
        return $tenant;
    }
}
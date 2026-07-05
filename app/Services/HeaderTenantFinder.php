<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Spatie\Multitenancy\TenantFinder\TenantFinder;
use Spatie\Multitenancy\Models\Tenant as SpatieTenant;

class HeaderTenantFinder extends TenantFinder
{
    /**
     * This method automatically overrides Spatie's internal boot cycle
     * to look up your tenant using custom headers or payload keys.
     */
    public function findForRequest(Request $request): ?SpatieTenant
    {
        $domain = $request->header('X-Tenant-Domain') 
            ?? $request->input('domain') 
            ?? $request->route('domain');

        if (!$domain) {
            return null;
        }

        // Directly query the Landlord table for the domain string
        return Tenant::where('domain', $domain)->first();
    }
}
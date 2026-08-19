<?php

namespace Tests\Feature\Tenant\Analyzer;

use App\Models\PlanFeature;
use App\Models\TenantSubscription;
use App\Support\Commercial\CommercialFeatureCatalog;
use Tests\Support\Tenant\AnalyzerManagementTestCase;

class TenantAnalyzerEntitlementAuthorizationTest extends AnalyzerManagementTestCase
{
    public function test_analyzer_routes_are_blocked_when_entitlement_is_disabled(): void
    {
        $context = $this->authenticatedTestResultContext(
            'analyzer-entitlement'
        );

        $this->withHeaders($context['headers'])
            ->getJson('/api/analyzers')
            ->assertForbidden();
    }

    public function test_receptionist_cannot_manage_analyzer_configuration(): void
    {
        $context = $this->authenticatedAnalyzerContext(
            'analyzer-permissions'
        );
        $branch = $this->headOfficeBranch($context['tenant']);

        $user = $this->createTenantUser($context['tenant'], [
            'role' => 'receptionist',
        ]);

        $token = $this->postJson('/api/login', [
            'domain' => $context['registration']['domain'],
            'email' => $user->email,
            'password' => 'Password123!',
        ])->assertOk()->json('token');

        $headers = $this->tenantHeaders(
            $token,
            $context['registration']['domain']
        );

        $this->withHeaders($headers)
            ->postJson('/api/analyzers', [
                'branch_uuid' => $branch->uuid,
                'code' => 'BLOCKED-01',
                'name' => 'Blocked Analyzer',
            ])
            ->assertForbidden();
    }
}

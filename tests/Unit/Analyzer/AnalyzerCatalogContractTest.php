<?php

namespace Tests\Unit\Analyzer;

use App\Support\Commercial\CommercialFeatureCatalog;
use App\Support\Tenant\TenantUserPermissionCatalog;
use PHPUnit\Framework\TestCase;

class AnalyzerCatalogContractTest extends TestCase
{
    public function test_analyzer_entitlement_exists_but_is_not_enabled_by_default(): void
    {
        $this->assertArrayHasKey(
            CommercialFeatureCatalog::ANALYZER_INTEGRATION,
            CommercialFeatureCatalog::labels()
        );

        $this->assertNotContains(
            CommercialFeatureCatalog::ANALYZER_INTEGRATION,
            CommercialFeatureCatalog::enabledByDefault(),
            true
        );
    }

    public function test_analyzer_permissions_follow_role_boundaries(): void
    {
        $this->assertTrue(TenantUserPermissionCatalog::allows(
            'admin',
            TenantUserPermissionCatalog::MANAGE_ANALYZERS
        ));

        $this->assertTrue(TenantUserPermissionCatalog::allows(
            'lab_technician',
            TenantUserPermissionCatalog::REVIEW_ANALYZER_IMPORTS
        ));

        $this->assertFalse(TenantUserPermissionCatalog::allows(
            'receptionist',
            TenantUserPermissionCatalog::VIEW_ANALYZERS
        ));
    }
}

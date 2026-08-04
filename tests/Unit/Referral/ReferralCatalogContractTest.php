<?php

namespace Tests\Unit\Referral;

use App\Support\Commercial\CommercialFeatureCatalog;
use App\Support\Tenant\TenantUserPermissionCatalog;
use PHPUnit\Framework\TestCase;

class ReferralCatalogContractTest extends TestCase
{
    public function test_referral_laboratories_are_enabled_in_the_validated_commercial_pack(): void
    {
        $this->assertContains(
            CommercialFeatureCatalog::REFERRAL_LABS,
            CommercialFeatureCatalog::enabledByDefault()
        );
    }

    public function test_referral_permissions_follow_the_approved_role_contract(): void
    {
        $all = [
            TenantUserPermissionCatalog::VIEW_REFERRALS,
            TenantUserPermissionCatalog::MANAGE_REFERRAL_LABORATORIES,
            TenantUserPermissionCatalog::CREATE_REFERRALS,
            TenantUserPermissionCatalog::DISPATCH_REFERRALS,
            TenantUserPermissionCatalog::CAPTURE_REFERRAL_RESULTS,
        ];

        foreach (['superadmin', 'admin'] as $role) {
            foreach ($all as $permission) {
                $this->assertTrue(
                    TenantUserPermissionCatalog::allows($role, $permission)
                );
            }
        }

        $this->assertTrue(TenantUserPermissionCatalog::allows(
            'lab_technician',
            TenantUserPermissionCatalog::VIEW_REFERRALS
        ));
        $this->assertTrue(TenantUserPermissionCatalog::allows(
            'lab_technician',
            TenantUserPermissionCatalog::CREATE_REFERRALS
        ));
        $this->assertTrue(TenantUserPermissionCatalog::allows(
            'lab_technician',
            TenantUserPermissionCatalog::DISPATCH_REFERRALS
        ));
        $this->assertTrue(TenantUserPermissionCatalog::allows(
            'lab_technician',
            TenantUserPermissionCatalog::CAPTURE_REFERRAL_RESULTS
        ));
        $this->assertFalse(TenantUserPermissionCatalog::allows(
            'lab_technician',
            TenantUserPermissionCatalog::MANAGE_REFERRAL_LABORATORIES
        ));

        foreach (['receptionist', 'doctor'] as $role) {
            $this->assertTrue(TenantUserPermissionCatalog::allows(
                $role,
                TenantUserPermissionCatalog::VIEW_REFERRALS
            ));
            $this->assertFalse(TenantUserPermissionCatalog::allows(
                $role,
                TenantUserPermissionCatalog::CREATE_REFERRALS
            ));
        }
    }
}

<?php

namespace App\Support\Tenant;

final class TenantUserPermissionCatalog
{
    public const VIEW_TEST_RESULTS = (
        'view_test_results'
    );

    public const CREATE_TEST_RESULTS = (
        'create_test_results'
    );

    public const EDIT_TEST_RESULTS = (
        'edit_test_results'
    );

    public const APPROVE_TEST_RESULTS = (
        'approve_test_results'
    );

    public const VIEW_LABORATORY_REPORTS = (
        'view_laboratory_reports'
    );

    public const GENERATE_LABORATORY_REPORTS = (
        'generate_laboratory_reports'
    );

    public const REQUEST_LABORATORY_REPORT_AMENDMENTS = (
        'request_laboratory_report_amendments'
    );

    public const APPROVE_LABORATORY_REPORT_AMENDMENTS = (
        'approve_laboratory_report_amendments'
    );

    public const MANAGE_TENANT_PROFILE = (
        'manage_tenant_profile'
    );

    public const VIEW_BRANCHES = 'view_branches';

    public const MANAGE_BRANCHES = 'manage_branches';

    public const VIEW_BILLING = 'view_billing';

    public const MANAGE_BILLING = 'manage_billing';

    public const MANAGE_PRICE_LISTS = 'manage_price_lists';

    public const RECORD_PAYMENTS = 'record_payments';

    public const VIEW_INVENTORY = 'view_inventory';

    public const MANAGE_INVENTORY_CATALOG = 'manage_inventory_catalog';

    public const RECEIVE_INVENTORY_STOCK = 'receive_inventory_stock';

    public const ADJUST_INVENTORY_STOCK = 'adjust_inventory_stock';

    public const RECORD_INVENTORY_CONSUMPTION = 'record_inventory_consumption';

    public const MANAGE_TEST_INVENTORY_REQUIREMENTS = 'manage_test_inventory_requirements';

    public const VIEW_REFERRALS = 'view_referrals';

    public const MANAGE_REFERRAL_LABORATORIES = 'manage_referral_laboratories';

    public const CREATE_REFERRALS = 'create_referrals';

    public const DISPATCH_REFERRALS = 'dispatch_referrals';

    public const CAPTURE_REFERRAL_RESULTS = 'capture_referral_results';

    public const VIEW_REFERRING_CLINICIANS = 'view_referring_clinicians';

    public const MANAGE_PORTAL_ACCESS = 'manage_portal_access';

    /**
     * Permissions enforced by tenant clinical workflows.
     *
     * @var array<string, array<int, string>>
     */
    private const ROLE_PERMISSIONS = [
        'superadmin' => [
            self::VIEW_TEST_RESULTS,
            self::CREATE_TEST_RESULTS,
            self::EDIT_TEST_RESULTS,
            self::APPROVE_TEST_RESULTS,
            self::VIEW_LABORATORY_REPORTS,
            self::GENERATE_LABORATORY_REPORTS,
            self::REQUEST_LABORATORY_REPORT_AMENDMENTS,
            self::APPROVE_LABORATORY_REPORT_AMENDMENTS,
            self::MANAGE_TENANT_PROFILE,
            self::VIEW_BRANCHES,
            self::MANAGE_BRANCHES,
            self::VIEW_BILLING,
            self::MANAGE_BILLING,
            self::MANAGE_PRICE_LISTS,
            self::RECORD_PAYMENTS,
            self::VIEW_INVENTORY,
            self::MANAGE_INVENTORY_CATALOG,
            self::RECEIVE_INVENTORY_STOCK,
            self::ADJUST_INVENTORY_STOCK,
            self::RECORD_INVENTORY_CONSUMPTION,
            self::MANAGE_TEST_INVENTORY_REQUIREMENTS,
            self::VIEW_REFERRALS,
            self::MANAGE_REFERRAL_LABORATORIES,
            self::CREATE_REFERRALS,
            self::DISPATCH_REFERRALS,
            self::CAPTURE_REFERRAL_RESULTS,
            self::VIEW_REFERRING_CLINICIANS,
            self::MANAGE_PORTAL_ACCESS,
        ],

        'admin' => [
            self::VIEW_TEST_RESULTS,
            self::CREATE_TEST_RESULTS,
            self::EDIT_TEST_RESULTS,
            self::APPROVE_TEST_RESULTS,
            self::VIEW_LABORATORY_REPORTS,
            self::GENERATE_LABORATORY_REPORTS,
            self::REQUEST_LABORATORY_REPORT_AMENDMENTS,
            self::APPROVE_LABORATORY_REPORT_AMENDMENTS,
            self::MANAGE_TENANT_PROFILE,
            self::VIEW_BRANCHES,
            self::MANAGE_BRANCHES,
            self::VIEW_BILLING,
            self::MANAGE_BILLING,
            self::MANAGE_PRICE_LISTS,
            self::RECORD_PAYMENTS,
            self::VIEW_INVENTORY,
            self::MANAGE_INVENTORY_CATALOG,
            self::RECEIVE_INVENTORY_STOCK,
            self::ADJUST_INVENTORY_STOCK,
            self::RECORD_INVENTORY_CONSUMPTION,
            self::MANAGE_TEST_INVENTORY_REQUIREMENTS,
            self::VIEW_REFERRALS,
            self::MANAGE_REFERRAL_LABORATORIES,
            self::CREATE_REFERRALS,
            self::DISPATCH_REFERRALS,
            self::CAPTURE_REFERRAL_RESULTS,
            self::VIEW_REFERRING_CLINICIANS,
            self::MANAGE_PORTAL_ACCESS,
        ],

        'lab_technician' => [
            self::VIEW_TEST_RESULTS,
            self::CREATE_TEST_RESULTS,
            self::EDIT_TEST_RESULTS,
            self::VIEW_LABORATORY_REPORTS,
            self::GENERATE_LABORATORY_REPORTS,
            self::REQUEST_LABORATORY_REPORT_AMENDMENTS,
            self::VIEW_BRANCHES,
            self::VIEW_INVENTORY,
            self::RECEIVE_INVENTORY_STOCK,
            self::RECORD_INVENTORY_CONSUMPTION,
            self::VIEW_REFERRALS,
            self::CREATE_REFERRALS,
            self::DISPATCH_REFERRALS,
            self::CAPTURE_REFERRAL_RESULTS,
            self::VIEW_REFERRING_CLINICIANS,
        ],

        'receptionist' => [
            self::VIEW_TEST_RESULTS,
            self::VIEW_LABORATORY_REPORTS,
            self::VIEW_BRANCHES,
            self::VIEW_BILLING,
            self::MANAGE_BILLING,
            self::RECORD_PAYMENTS,
            self::VIEW_REFERRALS,
            self::VIEW_REFERRING_CLINICIANS,
        ],

        'doctor' => [
            self::VIEW_TEST_RESULTS,
            self::APPROVE_TEST_RESULTS,
            self::VIEW_LABORATORY_REPORTS,
            self::GENERATE_LABORATORY_REPORTS,
            self::REQUEST_LABORATORY_REPORT_AMENDMENTS,
            self::APPROVE_LABORATORY_REPORT_AMENDMENTS,
            self::VIEW_BRANCHES,
            self::VIEW_BILLING,
            self::VIEW_REFERRALS,
            self::VIEW_REFERRING_CLINICIANS,
        ],
    ];

    /**
     * Determine whether a tenant role has a permission.
     */
    public static function allows(
        ?string $role,
        string $permission
    ): bool {
        if ($role === null || $role === '') {
            return false;
        }

        return in_array(
            $permission,
            self::ROLE_PERMISSIONS[$role] ?? [],
            true
        );
    }

    /**
     * Return all permissions belonging to a role.
     *
     * @return array<int, string>
     */
    public static function forRole(
        ?string $role
    ): array {
        if ($role === null || $role === '') {
            return [];
        }

        return self::ROLE_PERMISSIONS[$role] ?? [];
    }
}

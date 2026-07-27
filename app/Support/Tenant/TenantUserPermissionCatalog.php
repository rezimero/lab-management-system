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

    public const MANAGE_TENANT_PROFILE = (
        'manage_tenant_profile'
    );

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
            self::MANAGE_TENANT_PROFILE,
        ],

        'admin' => [
            self::VIEW_TEST_RESULTS,
            self::CREATE_TEST_RESULTS,
            self::EDIT_TEST_RESULTS,
            self::APPROVE_TEST_RESULTS,
            self::VIEW_LABORATORY_REPORTS,
            self::GENERATE_LABORATORY_REPORTS,
            self::MANAGE_TENANT_PROFILE,
        ],

        'lab_technician' => [
            self::VIEW_TEST_RESULTS,
            self::CREATE_TEST_RESULTS,
            self::EDIT_TEST_RESULTS,
            self::VIEW_LABORATORY_REPORTS,
            self::GENERATE_LABORATORY_REPORTS,
        ],

        'receptionist' => [
            self::VIEW_TEST_RESULTS,
            self::VIEW_LABORATORY_REPORTS,
        ],

        'doctor' => [
            self::VIEW_TEST_RESULTS,
            self::APPROVE_TEST_RESULTS,
            self::VIEW_LABORATORY_REPORTS,
            self::GENERATE_LABORATORY_REPORTS,
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

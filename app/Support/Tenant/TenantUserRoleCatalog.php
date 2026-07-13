<?php

namespace App\Support\Tenant;

final class TenantUserRoleCatalog
{
    /**
     * @var array<string, string>
     */
    private const ROLES = [
        'superadmin' => 'Super Administrator - Full system access',
        'admin' => 'Administrator - Manage all lab operations',
        'lab_technician' => 'Lab Technician - Perform and enter test results',
        'receptionist' => 'Receptionist - Register patients and manage appointments',
        'doctor' => 'Doctor - View patient data and approve results',
    ];

    /**
     * Return every recognized role name.
     *
     * @return array<int, string>
     */
    public static function keys(): array
    {
        return array_keys(self::ROLES);
    }

    /**
     * Return roles visible to a tenant user.
     *
     * @return array<string, string>
     */
    public function tenantRoles(): array
    {
        $roles = self::ROLES;

        unset($roles['superadmin']);

        return $roles;
    }

    /**
     * Return roles available for the current role.
     *
     * @return array<string, string>
     */
    public function availableFor(string $currentRole): array
    {
        if ($currentRole === 'superadmin') {
            return self::ROLES;
        }

        return $this->tenantRoles();
    }
}
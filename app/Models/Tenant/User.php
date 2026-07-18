<?php

namespace App\Models\Tenant;

use App\Support\Tenant\TenantUserPermissionCatalog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class User extends Authenticatable
{
    use HasApiTokens;
    use HasFactory;
    use Notifiable;
    use UsesTenantConnection;

    /**
     * Tenant database connection.
     */
    protected $connection = 'tenant';

    /**
     * Table name.
     */
    protected $table = 'users';

    /**
     * Mass-assignable attributes.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    /**
     * Hidden attributes.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Attribute casting.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Determine whether this user has a tenant permission.
     */
    public function hasPermission(
        string $permission
    ): bool {
        if (! $this->is_active) {
            return false;
        }

        return TenantUserPermissionCatalog::allows(
            $this->role,
            $permission
        );
    }

    /**
     * Determine whether this user is an administrator.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Determine whether this user is a laboratory technician.
     */
    public function isLabTechnician(): bool
    {
        return $this->role === 'lab_technician';
    }

    /**
     * Determine whether this user is a doctor.
     */
    public function isDoctor(): bool
    {
        return $this->role === 'doctor';
    }

    /**
     * Determine whether this user is a receptionist.
     */
    public function isReceptionist(): bool
    {
        return $this->role === 'receptionist';
    }
}

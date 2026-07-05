<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, UsesTenantConnection, HasRoles;

    /**
     * The connection name for the model.
     * This will be dynamically set by the tenant middleware
     */
    protected $connection = 'tenant';

    /**
     * The default guard name for this model.
     */
    protected $guard_name = 'sanctum';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    // Each user belongs to a tenant
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Check if user has a specific role
     * This overrides the Spatie method to also check the 'role' column
     */
    public function hasRole($role)
    {
        // First check Spatie's roles if available
        if ($this->roles()->exists()) {
            return parent::hasRole($role);
        }
        // Fallback to the 'role' column
        return $this->role === $role;
    }

    /**
     * Check if user is superadmin
     */
    public function isSuperAdmin()
    {
        return $this->role === 'superadmin';
    }

    /**
     * Check if user is admin (includes superadmin)
     */
    public function isAdmin()
    {
        return $this->role === 'admin' || $this->role === 'superadmin';
    }

    /**
     * Check if user can manage other users
     */
    public function canManageUsers()
    {
        return $this->isAdmin() || $this->isSuperAdmin();
    }
}
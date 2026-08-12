<?php

namespace App\Models;

use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Str;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class PatientPortalAccount extends Authenticatable
{
    use UsesTenantConnection;

    protected $connection = 'tenant';

    protected $table = 'patient_portal_accounts';

    protected $fillable = [
        'uuid',
        'patient_id',
        'email',
        'password',
        'email_verified_at',
        'activated_at',
        'last_login_at',
        'is_active',
        'created_by',
    ];

    protected $hidden = [
        'password',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'email_verified_at' => 'datetime',
            'activated_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(
            function (PatientPortalAccount $account): void {
                if (blank($account->uuid)) {
                    $account->uuid = (string) Str::uuid();
                }

                $account->email = self::normalizeEmail(
                    $account->email
                );
            }
        );

        static::updating(
            function (PatientPortalAccount $account): void {
                $account->email = self::normalizeEmail(
                    $account->email
                );
            }
        );
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            TenantUser::class,
            'created_by'
        );
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(
            PortalInvitation::class,
            'account_id'
        )->where(
            'account_type',
            PortalInvitation::ACCOUNT_PATIENT
        );
    }

    public function latestInvitation(): HasOne
    {
        return $this->hasOne(
            PortalInvitation::class,
            'account_id'
        )->where(
            'account_type',
            PortalInvitation::ACCOUNT_PATIENT
        )->latestOfMany();
    }

    public function accessTokens(): HasMany
    {
        return $this->hasMany(
            PortalAccessToken::class,
            'account_id'
        )->where(
            'account_type',
            PortalAccessToken::ACCOUNT_PATIENT
        );
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    private static function normalizeEmail(
        mixed $email
    ): string {
        return strtolower(trim((string) $email));
    }
}

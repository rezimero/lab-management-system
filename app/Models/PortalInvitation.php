<?php

namespace App\Models;

use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class PortalInvitation extends Model
{
    use UsesTenantConnection;

    public const ACCOUNT_PATIENT = 'patient';

    public const ACCOUNT_CLINICIAN = 'clinician';

    protected $connection = 'tenant';

    protected $table = 'portal_invitations';

    protected $fillable = [
        'uuid',
        'account_type',
        'account_id',
        'token_hash',
        'expires_at',
        'used_at',
        'revoked_at',
        'created_by',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected $casts = [
        'account_id' => 'integer',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'revoked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(
            function (PortalInvitation $invitation): void {
                if (blank($invitation->uuid)) {
                    $invitation->uuid = (string) Str::uuid();
                }
            }
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            TenantUser::class,
            'created_by'
        );
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}

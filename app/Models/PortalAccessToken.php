<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class PortalAccessToken extends Model
{
    use UsesTenantConnection;

    public const ACCOUNT_PATIENT = 'patient';

    public const ACCOUNT_CLINICIAN = 'clinician';

    protected $connection = 'tenant';

    protected $table = 'portal_access_tokens';

    protected $fillable = [
        'uuid',
        'account_type',
        'account_id',
        'token_hash',
        'name',
        'abilities',
        'last_used_at',
        'expires_at',
        'revoked_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected $casts = [
        'account_id' => 'integer',
        'abilities' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(
            function (PortalAccessToken $token): void {
                if (blank($token->uuid)) {
                    $token->uuid = (string) Str::uuid();
                }
            }
        );
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}

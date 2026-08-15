<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class PortalPasswordReset extends Model
{
    use UsesTenantConnection;

    protected $connection = 'tenant';

    protected $fillable = [
        'uuid',
        'account_type',
        'account_id',
        'token_hash',
        'requested_by_type',
        'requested_by_id',
        'expires_at',
        'used_at',
        'revoked_at',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected function casts(): array
    {
        return [
            'account_id' => 'integer',
            'requested_by_id' => 'integer',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'revoked_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(
            function (PortalPasswordReset $reset): void {
                if (blank($reset->uuid)) {
                    $reset->uuid = (string) Str::uuid();
                }
            }
        );
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}

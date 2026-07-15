<?php

namespace App\Models;

use App\Models\Landlord\User as LandlordUser;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    /**
     * Store every access token centrally in the landlord database.
     */
    protected $connection = 'landlord';

    /**
     * Access-token table.
     */
    protected $table = 'personal_access_tokens';

    /**
     * Attribute casts.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'tenant_id' => 'integer',
        'abilities' => 'json',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Configure tenant-aware token creation and lookup.
     */
    protected static function booted(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Bind New Tokens to Their Authentication Context
        |--------------------------------------------------------------------------
        */

        static::creating(
            function (PersonalAccessToken $token): void {
                $tenantUserType = static::tenantUserMorphClass();
                $landlordUserType = static::landlordUserMorphClass();

                if ($token->tokenable_type === $tenantUserType) {
                    $tenant = Tenant::current();

                    if (! $tenant) {
                        throw new LogicException(
                            'A tenant access token cannot be created '
                            .'without an active tenant context.'
                        );
                    }

                    $token->tenant_id = (int) $tenant->getKey();

                    return;
                }

                if ($token->tokenable_type === $landlordUserType) {
                    $token->tenant_id = null;

                    return;
                }

                throw new LogicException(
                    sprintf(
                        'Unsupported access-token owner type [%s].',
                        (string) $token->tokenable_type
                    )
                );
            }
        );

        /*
        |--------------------------------------------------------------------------
        | Restrict Token Queries to the Current Authentication Context
        |--------------------------------------------------------------------------
        |
        | Sanctum calls this model when resolving bearer tokens. The scope
        | guarantees that a token created for one tenant cannot authenticate
        | a user with the same internal ID in another tenant database.
        |
        */

        static::addGlobalScope(
            'authentication-context',
            function (Builder $builder): void {
                $table = $builder->getModel()->getTable();
                $tenant = Tenant::current();

                if ($tenant) {
                    $builder
                        ->where(
                            $table.'.tenant_id',
                            (int) $tenant->getKey()
                        )
                        ->where(
                            $table.'.tokenable_type',
                            static::tenantUserMorphClass()
                        );

                    return;
                }

                $builder
                    ->whereNull($table.'.tenant_id')
                    ->where(
                        $table.'.tokenable_type',
                        static::landlordUserMorphClass()
                    );
            }
        );
    }

    /**
     * Get the tenant that owns the access token.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(
            Tenant::class,
            'tenant_id'
        );
    }

    /**
     * Resolve the stored morph type for tenant users.
     */
    private static function tenantUserMorphClass(): string
    {
        return (new TenantUser())->getMorphClass();
    }

    /**
     * Resolve the stored morph type for landlord users.
     */
    private static function landlordUserMorphClass(): string
    {
        return (new LandlordUser())->getMorphClass();
    }
}
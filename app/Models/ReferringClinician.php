<?php

namespace App\Models;

use App\Models\Tenant\User as TenantUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class ReferringClinician extends Model
{
    use HasFactory;
    use SoftDeletes;
    use UsesTenantConnection;

    protected $connection = 'tenant';

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'phone',
        'organisation',
        'registration_number',
        'specialty',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(
            function (ReferringClinician $clinician): void {
                if (blank($clinician->uuid)) {
                    $clinician->uuid = (string) Str::uuid();
                }

                $clinician->email = self::normalizeEmail(
                    $clinician->email
                );
            }
        );

        static::updating(
            function (ReferringClinician $clinician): void {
                $clinician->email = self::normalizeEmail(
                    $clinician->email
                );
            }
        );
    }

    public function portalAccount(): HasOne
    {
        return $this->hasOne(
            ClinicianPortalAccount::class,
            'referring_clinician_id'
        );
    }

    public function orders(): HasMany
    {
        return $this->hasMany(
            LaboratoryOrder::class,
            'referring_clinician_id'
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            TenantUser::class,
            'created_by'
        );
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(
            TenantUser::class,
            'updated_by'
        );
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    private static function normalizeEmail(
        mixed $email
    ): ?string {
        if (! is_string($email)) {
            return null;
        }

        $email = strtolower(trim($email));

        return $email === ''
            ? null
            : $email;
    }
}

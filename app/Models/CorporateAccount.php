<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CorporateAccount extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';

    protected $fillable = [
        'uuid',
        'code',
        'name',
        'contact_name',
        'email',
        'phone',
        'billing_address',
        'payment_terms_days',
        'credit_limit',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'payment_terms_days' => 'integer',
        'credit_limit' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (CorporateAccount $account): void {
            if (blank($account->uuid)) {
                $account->uuid = (string) Str::uuid();
            }
        });
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
}

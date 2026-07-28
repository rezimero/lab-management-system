<?php

namespace App\Models;

use App\Models\Concerns\HasMedia;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantProfile extends Model
{
    use HasFactory;
    use HasMedia;

    protected $connection = 'landlord';

    protected $fillable = [
        'tenant_id',

        /*
        |--------------------------------------------------------------------------
        | Identity
        |--------------------------------------------------------------------------
        */
        'lab_name',
        'tagline',

        /*
        |--------------------------------------------------------------------------
        | Branding
        |--------------------------------------------------------------------------
        */
        'primary_color',
        'secondary_color',
        'accent_color',

        /*
        |--------------------------------------------------------------------------
        | Contact
        |--------------------------------------------------------------------------
        */
        'email',
        'phone',
        'website',

        /*
        |--------------------------------------------------------------------------
        | Address
        |--------------------------------------------------------------------------
        */
        'address',
        'city',
        'state',
        'country',

        /*
        |--------------------------------------------------------------------------
        | Legal
        |--------------------------------------------------------------------------
        */
        'license_number',
        'registration_number',
        'tin',

        /*
        |--------------------------------------------------------------------------
        | Reports
        |--------------------------------------------------------------------------
        */
        'report_footer',
        'director_name',
        'director_qualification',
        'director_registration_number',
        'signatory_designation',
        'accreditation_details',

        /*
        |--------------------------------------------------------------------------
        | Invoices
        |--------------------------------------------------------------------------
        */
        'invoice_footer',
        'bank_name',
        'account_name',
        'account_number',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Tenant that owns this laboratory profile.
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Get the formatted laboratory address.
     */
    public function getFullAddressAttribute(): string
    {
        return collect([
            $this->address,
            $this->city,
            $this->state,
            $this->country,
        ])
            ->filter()
            ->implode(', ');
    }

    /**
     * Laboratory logo URL.
     */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('logo');
    }

    /**
     * Laboratory favicon URL.
     */
    public function getFaviconUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('favicon');
    }

    /**
     * Laboratory director signature URL.
     */
    public function getDirectorSignatureUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('director_signature');
    }
}
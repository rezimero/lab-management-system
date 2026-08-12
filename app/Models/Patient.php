<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class Patient extends Model
{
    use HasFactory, UsesTenantConnection;

    protected $fillable = [
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'email',
        'phone',
        'address',
        'medical_record_number',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'is_active' => 'boolean',
    ];

    // Relationship with test results
    public function testResults()
    {
        return $this->hasMany(TestResult::class);
    }

    public function portalAccount(): HasOne
    {
        return $this->hasOne(
            PatientPortalAccount::class,
            'patient_id'
        );
    }

    // Accessor for full name
    public function getFullNameAttribute()
    {
        return $this->first_name.' '.$this->last_name;
    }
}

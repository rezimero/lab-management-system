<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class TestResult extends Model
{
    use HasFactory, UsesTenantConnection;

    protected $fillable = [
        'patient_id',
        'lab_test_id',
        'user_id',
        'test_date',
        'results_data',
        'notes',
        'status',
        'completed_at',
    ];

    protected $casts = [
        'test_date' => 'date',
        'results_data' => 'json',
        'completed_at' => 'datetime',
    ];

    // Relationships
    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function labTest()
    {
        return $this->belongsTo(LabTest::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class LabTest extends Model
{
    use HasFactory, UsesTenantConnection;

    protected $fillable = [
        'name',
        'description',
        'category',
        'price',
        'turnaround_time_hours',
        'parameters',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'turnaround_time_hours' => 'integer',
        'parameters' => 'json',
        'is_active' => 'boolean',
    ];

    // Relationship with test results
    public function testResults()
    {
        return $this->hasMany(TestResult::class);
    }
}
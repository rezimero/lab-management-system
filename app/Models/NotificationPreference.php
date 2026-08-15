<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Multitenancy\Models\Concerns\UsesTenantConnection;

class NotificationPreference extends Model
{
    use UsesTenantConnection;

    protected $connection = 'tenant';

    protected $fillable = [
        'recipient_type',
        'recipient_id',
        'category',
        'in_app_enabled',
        'email_enabled',
        'sms_enabled',
        'whatsapp_enabled',
    ];

    protected function casts(): array
    {
        return [
            'recipient_id' => 'integer',
            'in_app_enabled' => 'boolean',
            'email_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
            'whatsapp_enabled' => 'boolean',
        ];
    }
}

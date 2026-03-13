<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGatewaySetting extends Model
{
    protected $fillable = [
        'provider',
        'display_name',
        'is_enabled',
        'test_mode',
        'key_id',
        'key_secret',
        'webhook_secret',
        'currency',
        'description',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'test_mode' => 'boolean',
        'key_secret' => 'encrypted',
        'webhook_secret' => 'encrypted',
    ];
}

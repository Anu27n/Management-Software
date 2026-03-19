<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationSetting extends Model
{
    protected $fillable = [
        'mail_enabled',
        'mail_from_name',
        'mail_from_address',
        'mail_host',
        'mail_port',
        'mail_username',
        'mail_password',
        'mail_encryption',
        'sms_enabled',
        'sms_provider',
        'sms_sender_id',
        'sms_api_key',
        'sms_api_secret',
    ];

    protected $casts = [
        'mail_enabled' => 'boolean',
        'sms_enabled' => 'boolean',
    ];
}

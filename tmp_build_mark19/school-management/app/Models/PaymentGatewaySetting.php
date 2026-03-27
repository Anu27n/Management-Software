<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Crypt;

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
    ];

    protected function keySecret(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->decryptCredential($value),
            set: fn ($value) => $this->encryptCredential($value),
        );
    }

    protected function webhookSecret(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => $this->decryptCredential($value),
            set: fn ($value) => $this->encryptCredential($value),
        );
    }

    protected function decryptCredential($value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return Crypt::decryptString((string) $value);
        } catch (DecryptException) {
            // Gracefully handle records encrypted with a different app key.
            if ($this->isLaravelEncryptedPayload((string) $value)) {
                return null;
            }

            // Backward compatibility for older plain-text values.
            return (string) $value;
        }
    }

    protected function encryptCredential($value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return Crypt::encryptString((string) $value);
    }

    protected function isLaravelEncryptedPayload(string $value): bool
    {
        $decoded = base64_decode($value, true);

        if ($decoded === false) {
            return false;
        }

        $payload = json_decode($decoded, true);

        return is_array($payload)
            && array_key_exists('iv', $payload)
            && array_key_exists('value', $payload)
            && array_key_exists('mac', $payload);
    }
}

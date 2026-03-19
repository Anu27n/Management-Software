<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class UserCredentialSupport
{
    public static function generateUniqueUsername(?string $seed, ?int $ignoreUserId = null): string
    {
        if (!Schema::hasColumn('users', 'username')) {
            return 'user';
        }

        $base = Str::of((string) $seed)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '.')
            ->trim('.')
            ->value();

        if ($base === '') {
            $base = 'user';
        }

        $base = Str::limit($base, 24, '');
        $candidate = $base;
        $suffix = 1;

        while (self::usernameExists($candidate, $ignoreUserId)) {
            $suffix++;
            $suffixText = (string) $suffix;
            $availableBaseLength = max(1, 24 - strlen($suffixText) - 1);
            $candidate = substr($base, 0, $availableBaseLength) . '.' . $suffixText;
        }

        return $candidate;
    }

    public static function generateTemporaryPassword(int $length = 10): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
        $maxIndex = strlen($alphabet) - 1;
        $password = '';

        for ($i = 0; $i < $length; $i++) {
            $password .= $alphabet[random_int(0, $maxIndex)];
        }

        return $password;
    }

    private static function usernameExists(string $username, ?int $ignoreUserId = null): bool
    {
        if (!Schema::hasColumn('users', 'username')) {
            return false;
        }

        return User::query()
            ->when($ignoreUserId, fn ($query) => $query->where('id', '!=', $ignoreUserId))
            ->whereRaw('LOWER(username) = ?', [strtolower($username)])
            ->exists();
    }
}

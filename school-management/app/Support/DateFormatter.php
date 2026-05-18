<?php

namespace App\Support;

use Carbon\CarbonInterface;
use DateTimeInterface;
use Illuminate\Support\Carbon;

class DateFormatter
{
    public static function display(DateTimeInterface|string|null $value, string $fallback = '-'): string
    {
        if (blank($value)) {
            return $fallback;
        }

        if ($value instanceof DateTimeInterface) {
            return Carbon::instance($value)->format('d m y');
        }

        try {
            return Carbon::parse($value)->format('d m y');
        } catch (\Throwable) {
            return $fallback;
        }
    }

    public static function fromCarbon(?CarbonInterface $value, string $fallback = '-'): string
    {
        return self::display($value, $fallback);
    }
}

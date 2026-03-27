<?php

namespace App\Support;

class ClassEligibility
{
    public static function isRteEligible(?string $className): bool
    {
        $normalized = strtolower(trim((string) $className));

        if ($normalized === '') {
            return false;
        }

        if (preg_match('/\b([1-8])(?:st|nd|rd|th)?\b/', $normalized, $matches) === 1) {
            return (int) $matches[1] <= 8;
        }

        $wordToNumber = [
            'one' => 1,
            'two' => 2,
            'three' => 3,
            'four' => 4,
            'five' => 5,
            'six' => 6,
            'seven' => 7,
            'eight' => 8,
            'i' => 1,
            'ii' => 2,
            'iii' => 3,
            'iv' => 4,
            'v' => 5,
            'vi' => 6,
            'vii' => 7,
            'viii' => 8,
        ];

        foreach ($wordToNumber as $word => $number) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/', $normalized) === 1) {
                return $number <= 8;
            }
        }

        return false;
    }
}

<?php

namespace App\Support;

use Illuminate\Support\Arr;

class ReportTemplateRegistry
{
    private const TEMPLATES = [
        'semester_1' => [
            'label' => '1st Semester',
            'title' => '1st Semester Marksheet',
            'exam_name' => '1st Semester',
            'term_number' => 1,
            'comparative' => false,
        ],
        'semester_2' => [
            'label' => 'Final / 2nd Semester',
            'title' => 'Final / 2nd Semester Marksheet',
            'exam_name' => 'Final / 2nd Semester',
            'term_number' => 2,
            'comparative' => true,
        ],
        'unit_test_round_1_9_12' => [
            'label' => 'Unit Test Round 1 (9-12)',
            'title' => 'Unit Test Round 1 Marksheet (Classes 9-12)',
            'exam_name' => 'Unit Test Round 1',
            'term_number' => 1,
            'comparative' => false,
        ],
        'unit_test_round_2_9_12' => [
            'label' => 'Unit Test Round 2 (9-12)',
            'title' => 'Unit Test Round 2 Marksheet (Classes 9-12)',
            'exam_name' => 'Unit Test Round 2',
            'term_number' => 2,
            'comparative' => false,
        ],
    ];

    public static function all(): array
    {
        return self::TEMPLATES;
    }

    public static function keys(): array
    {
        return array_keys(self::TEMPLATES);
    }

    public static function isSupported(?string $template): bool
    {
        return $template !== null && array_key_exists($template, self::TEMPLATES);
    }

    public static function meta(string $template): array
    {
        return self::TEMPLATES[$template] ?? self::TEMPLATES['semester_1'];
    }

    public static function label(string $template): string
    {
        return (string) Arr::get(self::meta($template), 'label', '1st Semester');
    }

    public static function title(string $template): string
    {
        return (string) Arr::get(self::meta($template), 'title', '1st Semester Marksheet');
    }

    public static function examName(string $template): string
    {
        return (string) Arr::get(self::meta($template), 'exam_name', '1st Semester');
    }

    public static function termNumber(string $template): int
    {
        return (int) Arr::get(self::meta($template), 'term_number', 1);
    }

    public static function isComparative(string $template): bool
    {
        return (bool) Arr::get(self::meta($template), 'comparative', false);
    }
}

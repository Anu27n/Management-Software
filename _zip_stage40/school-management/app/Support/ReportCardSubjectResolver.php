<?php

namespace App\Support;

use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Support\Collection;

class ReportCardSubjectResolver
{
    private const GRADING_SUBJECTS = [
        'Moral Science / Financial Literacy',
        'Art',
        'Music',
        'Physical Training',
        'S.U.P.W.',
        'Attendance',
    ];

    public function getScholasticSubjects(SchoolClass $class): Collection
    {
        $subjects = $class->subjects()
            ->where('category', 'scholastic')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        if ($subjects->isNotEmpty()) {
            return $subjects;
        }

        return $this->seedFallbackSubjects($class, 'scholastic');
    }

    public function getGradingSubjects(SchoolClass $class): Collection
    {
        $subjects = $class->subjects()
            ->where('category', 'grading')
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        if ($subjects->isNotEmpty()) {
            return $subjects;
        }

        return $this->seedFallbackSubjects($class, 'grading');
    }

    private function seedFallbackSubjects(SchoolClass $class, string $category): Collection
    {
        $names = $category === 'grading'
            ? self::GRADING_SUBJECTS
            : $this->fallbackScholasticSubjects($class);

        $subjects = collect();

        foreach ($names as $index => $name) {
            $subjects->push(Subject::firstOrCreate(
                [
                    'class_id' => $class->id,
                    'name' => $name,
                ],
                [
                    'code' => strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $name), 0, 6)) ?: 'SUBJ',
                    'category' => $category,
                    'display_order' => $index + 1,
                ]
            ));
        }

        return $subjects->sortBy('display_order')->values();
    }

    private function fallbackScholasticSubjects(SchoolClass $class): array
    {
        $classNumber = $class->numeric_name ?: (int) preg_replace('/\D+/', '', (string) $class->name);

        if ($classNumber >= 9) {
            return [
                'English I',
                'English II',
                'Hindi',
                'History & Civics',
                'Geography',
                'Mathematics',
                'Physics',
                'Chemistry',
                'Biology',
                'Commerce / Commercial Studies',
                'Economics',
                'Accounts',
                'Computer Science / Applications',
                'Indian Music',
                'Art',
            ];
        }

        return [
            'English I',
            'English II',
            'Mathematics',
            'Hindi',
            'General Science',
            'Physics',
            'Chemistry',
            'Biology',
            'Social Studies',
            'History & Civics',
            'Geography',
            'Computers',
            'Sanskrit',
            'General Knowledge',
        ];
    }
}

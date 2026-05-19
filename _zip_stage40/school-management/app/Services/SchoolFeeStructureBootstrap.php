<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\SchoolClass;
use App\Support\QuarterlyFeeDueDates;
use App\Support\SchoolFeeConfig;
use Illuminate\Support\Facades\DB;

final class SchoolFeeStructureBootstrap
{
    /**
     * Creates default fee categories + structures for the active (or latest) academic year
     * from config/school_fees.php. Safe to run multiple times (skips if already seeded for that year).
     */
    public static function run(): void
    {
        $config = SchoolFeeConfig::resolved();
        if ($config === []) {
            return;
        }

        $academicYear = AcademicYear::query()
            ->where('is_active', true)
            ->orderByDesc('start_date')
            ->first()
            ?? AcademicYear::query()->orderByDesc('start_date')->first();

        if (!$academicYear) {
            return;
        }

        $quarterlyNames = $config['idempotency_quarterly_category_names'] ?? ['Quarterly Fees (Every Quarter)'];
        $alreadySeeded = FeeStructure::query()
            ->where('academic_year_id', $academicYear->id)
            ->whereHas('feeCategory', fn ($q) => $q->whereIn('name', $quarterlyNames))
            ->exists();

        if ($alreadySeeded) {
            return;
        }

        $classes = SchoolClass::query()->orderBy('id')->get();
        if ($classes->isEmpty()) {
            return;
        }

        $quarterDates = QuarterlyFeeDueDates::dueDatesWithinAcademicYear($academicYear);
        if ($quarterDates === []) {
            return;
        }

        $cats = $config['categories'] ?? [];
        $catQuarterly = FeeCategory::firstOrCreate(
            ['name' => $cats['quarterly']['name'] ?? 'Quarterly Fees (Every Quarter)'],
            ['description' => $cats['quarterly']['description'] ?? null]
        );
        $catMisc = FeeCategory::firstOrCreate(
            ['name' => $cats['misc']['name'] ?? 'Miscellaneous Charges (Annual)'],
            ['description' => $cats['misc']['description'] ?? null]
        );
        $catReg = FeeCategory::firstOrCreate(
            ['name' => $cats['registration']['name'] ?? 'Registration & Prospectus'],
            ['description' => $cats['registration']['description'] ?? null]
        );
        $catAdmission = FeeCategory::firstOrCreate(
            ['name' => $cats['admission']['name'] ?? 'Admission Fees'],
            ['description' => $cats['admission']['description'] ?? null]
        );
        $catSecurity = FeeCategory::firstOrCreate(
            ['name' => $cats['security']['name'] ?? 'Security Deposit (Refundable)'],
            ['description' => $cats['security']['description'] ?? null]
        );

        $miscAnnual = (float) ($config['amounts']['misc_annual'] ?? 1000);
        $admissionFee = (float) ($config['amounts']['admission_one_time'] ?? 6000);

        DB::transaction(function () use (
            $classes,
            $academicYear,
            $quarterDates,
            $catQuarterly,
            $catMisc,
            $catReg,
            $catAdmission,
            $catSecurity,
            $miscAnnual,
            $admissionFee,
            $config
        ) {
            foreach ($classes as $class) {
                $grade = self::gradeFromSchoolClass($class);
                if ($grade === null) {
                    continue;
                }

                $tierAmounts = self::amountsForGrade($grade, $config['tiers'] ?? []);
                if ($tierAmounts === null) {
                    continue;
                }

                [$quarterlyAmount, $regAmount, $securityAmount] = $tierAmounts;

                foreach ($quarterDates as $due) {
                    FeeStructure::create([
                        'fee_category_id' => $catQuarterly->id,
                        'class_id' => $class->id,
                        'academic_year_id' => $academicYear->id,
                        'amount' => $quarterlyAmount,
                        'frequency' => 'quarterly',
                        'due_date' => $due->format('Y-m-d'),
                        'applies_to' => 'all_students',
                    ]);
                }

                FeeStructure::create([
                    'fee_category_id' => $catMisc->id,
                    'class_id' => $class->id,
                    'academic_year_id' => $academicYear->id,
                    'amount' => $miscAnnual,
                    'frequency' => 'yearly',
                    'due_date' => $academicYear->start_date,
                    'applies_to' => 'all_students',
                ]);

                FeeStructure::create([
                    'fee_category_id' => $catReg->id,
                    'class_id' => $class->id,
                    'academic_year_id' => $academicYear->id,
                    'amount' => $regAmount,
                    'frequency' => 'one_time',
                    'due_date' => null,
                    'applies_to' => 'new_admission_only',
                ]);

                FeeStructure::create([
                    'fee_category_id' => $catAdmission->id,
                    'class_id' => $class->id,
                    'academic_year_id' => $academicYear->id,
                    'amount' => $admissionFee,
                    'frequency' => 'one_time',
                    'due_date' => null,
                    'applies_to' => 'new_admission_only',
                ]);

                FeeStructure::create([
                    'fee_category_id' => $catSecurity->id,
                    'class_id' => $class->id,
                    'academic_year_id' => $academicYear->id,
                    'amount' => $securityAmount,
                    'frequency' => 'one_time',
                    'due_date' => null,
                    'applies_to' => 'new_admission_only',
                ]);
            }
        });
    }

    public static function gradeFromSchoolClass(SchoolClass $class): ?int
    {
        $fromNumeric = self::gradeFromNumericName($class->numeric_name);
        if ($fromNumeric !== null && $fromNumeric >= 1 && $fromNumeric <= 12) {
            return $fromNumeric;
        }

        return self::gradeFromClassName((string) $class->name);
    }

    private static function gradeFromNumericName(?string $numericName): ?int
    {
        if ($numericName === null || $numericName === '') {
            return null;
        }

        if (!is_numeric($numericName)) {
            return null;
        }

        return (int) $numericName;
    }

    private static function gradeFromClassName(string $name): ?int
    {
        $name = strtoupper(trim($name));

        $romans = [
            'XII' => 12, 'XI' => 11, 'X' => 10, 'IX' => 9, 'VIII' => 8, 'VII' => 7,
            'VI' => 6, 'V' => 5, 'IV' => 4, 'III' => 3, 'II' => 2, 'I' => 1,
        ];
        foreach ($romans as $roman => $value) {
            if (preg_match('/\b' . $roman . '\b/', $name)) {
                return $value;
            }
        }

        if (preg_match('/\b(1[0-2]|[1-9])\b/', $name, $m)) {
            $n = (int) $m[1];
            if ($n >= 1 && $n <= 12) {
                return $n;
            }
        }

        return null;
    }

    /**
     * @param  list<array{grade_min: int, grade_max: int, quarterly: float, registration: float, security: float}>  $tiers
     * @return array{0: float, 1: float, 2: float}|null quarterly, registration, security
     */
    private static function amountsForGrade(int $grade, array $tiers): ?array
    {
        foreach ($tiers as $tier) {
            $min = (int) ($tier['grade_min'] ?? 0);
            $max = (int) ($tier['grade_max'] ?? 0);
            if ($grade >= $min && $grade <= $max) {
                return [
                    (float) ($tier['quarterly'] ?? 0),
                    (float) ($tier['registration'] ?? 0),
                    (float) ($tier['security'] ?? 0),
                ];
            }
        }

        return null;
    }
}

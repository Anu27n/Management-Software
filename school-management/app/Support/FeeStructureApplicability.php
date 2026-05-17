<?php

namespace App\Support;

use App\Models\FeeStructure;
use App\Models\Student;

final class FeeStructureApplicability
{
    public static function appliesToStudent(FeeStructure $structure, Student $student): bool
    {
        if (($structure->applies_to ?? 'all_students') !== 'new_admission_only') {
            return true;
        }

        return self::studentIsNewAdmissionForAcademicYear($student);
    }

    public static function studentIsNewAdmissionForAcademicYear(Student $student): bool
    {
        if (!$student->admission_date) {
            return false;
        }

        $student->loadMissing('academicYear');
        $year = $student->academicYear;
        if (!$year) {
            return false;
        }

        $admission = $student->admission_date->copy()->startOfDay();
        $start = $year->start_date->copy()->startOfYear()->startOfDay();
        $end = $year->end_date->copy()->startOfDay();

        return $admission->greaterThanOrEqualTo($start) && $admission->lessThanOrEqualTo($end);
    }
}

<?php

namespace App\Support;

use App\Models\AcademicYear;
use Carbon\Carbon;
use Carbon\CarbonInterface;

final class QuarterlyFeeDueDates
{
    /**
     * Due on the 15th of April, July, October, and January when that date falls
     * within the academic year's start_date and end_date (inclusive).
     *
     * @return list<CarbonInterface>
     */
    public static function dueDatesWithinAcademicYear(AcademicYear $academicYear): array
    {
        $start = Carbon::parse($academicYear->start_date)->startOfDay();
        $end = Carbon::parse($academicYear->end_date)->startOfDay();

        $found = [];

        for ($year = $start->year; $year <= $end->year + 1; $year++) {
            foreach ([4, 7, 10, 1] as $month) {
                try {
                    $d = Carbon::create($year, $month, 15)->startOfDay();
                } catch (\Throwable) {
                    continue;
                }

                if ($d->lt($start) || $d->gt($end)) {
                    continue;
                }

                $found[$d->toDateString()] = $d;
            }
        }

        return collect($found)->sortKeys()->values()->all();
    }
}

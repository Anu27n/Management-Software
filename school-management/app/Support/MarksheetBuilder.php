<?php

namespace App\Support;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Student;
use App\Models\StudentExamReport;

class MarksheetBuilder
{
    public function __construct(
        private ReportCardSubjectResolver $subjectResolver = new ReportCardSubjectResolver()
    ) {
    }

    public function build(Student $student, Exam $selectedExam): array
    {
        $class = $student->schoolClass;
        $firstSemesterExam = $selectedExam->resolved_template === 'semester_1'
            ? $selectedExam
            : $this->findSemesterExam($selectedExam->academic_year_id, 'semester_1');
        $secondSemesterExam = $selectedExam->resolved_template === 'semester_2'
            ? $selectedExam
            : null;

        $scholasticSubjects = $this->subjectResolver->getScholasticSubjects($class);
        $gradingSubjects = $this->subjectResolver->getGradingSubjects($class);

        $studentResults = ExamResult::query()
            ->with('subject')
            ->where('student_id', $student->id)
            ->whereIn('exam_id', collect([$firstSemesterExam?->id, $secondSemesterExam?->id])->filter())
            ->get()
            ->groupBy(fn (ExamResult $result) => $result->exam_id . ':' . $result->subject_id);

        $firstReport = $firstSemesterExam
            ? StudentExamReport::where('exam_id', $firstSemesterExam->id)->where('student_id', $student->id)->first()
            : null;
        $secondReport = $secondSemesterExam
            ? StudentExamReport::where('exam_id', $secondSemesterExam->id)->where('student_id', $student->id)->first()
            : null;

        $subjectRows = $scholasticSubjects->map(function ($subject) use ($studentResults, $firstSemesterExam, $secondSemesterExam) {
            $first = $firstSemesterExam ? $studentResults->get($firstSemesterExam->id . ':' . $subject->id)?->first() : null;
            $second = $secondSemesterExam ? $studentResults->get($secondSemesterExam->id . ':' . $subject->id)?->first() : null;
            $firstTotal = (float) ($first?->calculated_total ?? $first?->marks_obtained ?? 0);
            $secondTotal = (float) ($second?->calculated_total ?? $second?->marks_obtained ?? 0);
            $yearlyAverage = $secondSemesterExam ? round(($firstTotal + $secondTotal) / 2, 2) : null;
            $yearlyAveragePercentage = $secondSemesterExam
                ? round((($firstTotal + $secondTotal) / 2), 2)
                : round($firstTotal, 2);

            return [
                'subject' => $subject,
                'first' => $first,
                'second' => $second,
                'first_total' => $firstTotal,
                'second_total' => $secondTotal,
                'yearly_average' => $yearlyAverage,
                'yearly_average_percentage' => $yearlyAveragePercentage,
            ];
        });

        $gradingRows = $gradingSubjects->map(function ($subject) use ($studentResults, $firstSemesterExam, $secondSemesterExam) {
            return [
                'subject' => $subject,
                'first_grade' => $firstSemesterExam ? optional($studentResults->get($firstSemesterExam->id . ':' . $subject->id)?->first())->grade : null,
                'second_grade' => $secondSemesterExam ? optional($studentResults->get($secondSemesterExam->id . ':' . $subject->id)?->first())->grade : null,
            ];
        });

        $firstSemesterGrandTotal = round($subjectRows->sum('first_total'), 2);
        $secondSemesterGrandTotal = round($subjectRows->sum('second_total'), 2);
        $yearlyGrandTotal = round($subjectRows->sum('yearly_average'), 2);
        $maxMarks = $scholasticSubjects->count() * 100;
        $percentage = $selectedExam->resolved_template === 'semester_2'
            ? $this->percentage($yearlyGrandTotal, $maxMarks)
            : $this->percentage($firstSemesterGrandTotal, $maxMarks);

        return [
            'selected_exam' => $selectedExam,
            'first_semester_exam' => $firstSemesterExam,
            'second_semester_exam' => $secondSemesterExam,
            'student' => $student,
            'subject_rows' => $subjectRows,
            'grading_rows' => $gradingRows,
            'first_report' => $firstReport,
            'second_report' => $secondReport,
            'personal_attributes' => [
                'first' => $firstReport?->personal_attributes ?? [],
                'second' => $secondReport?->personal_attributes ?? [],
            ],
            'totals' => [
                'first_semester_total' => $firstSemesterGrandTotal,
                'second_semester_total' => $secondSemesterGrandTotal,
                'yearly_grand_total' => $yearlyGrandTotal,
                'max_marks' => $maxMarks,
                'percentage' => $percentage,
            ],
            'rank' => $this->calculateRank($selectedExam, $student, $subjectRows->pluck('subject.id')->all(), $maxMarks),
            'result_label' => $selectedExam->resolved_template === 'semester_2'
                ? ($secondReport?->final_result === 'detained' ? 'Detained' : 'Promoted')
                : null,
        ];
    }

    public function calculateRank(Exam $exam, Student $student, array $subjectIds, int|float $maxMarks): ?int
    {
        $students = Student::query()
            ->where('class_id', $student->class_id)
            ->where('section_id', $student->section_id)
            ->where('status', 'active')
            ->get(['id']);

        if ($students->isEmpty()) {
            return null;
        }

        $firstExam = $exam->resolved_template === 'semester_2'
            ? $this->findSemesterExam($exam->academic_year_id, 'semester_1')
            : $exam;

        $results = ExamResult::query()
            ->whereIn('student_id', $students->pluck('id'))
            ->whereIn('subject_id', $subjectIds)
            ->whereIn('exam_id', collect([$firstExam?->id, $exam->resolved_template === 'semester_2' ? $exam->id : null])->filter())
            ->get()
            ->groupBy(fn (ExamResult $result) => $result->student_id . ':' . $result->exam_id . ':' . $result->subject_id);

        $scores = [];

        foreach ($students as $classStudent) {
            $grandTotal = 0;

            foreach ($subjectIds as $subjectId) {
                $first = $firstExam ? $results->get($classStudent->id . ':' . $firstExam->id . ':' . $subjectId)?->first() : null;
                $firstTotal = (float) ($first?->calculated_total ?? $first?->marks_obtained ?? 0);

                if ($exam->resolved_template === 'semester_2') {
                    $second = $results->get($classStudent->id . ':' . $exam->id . ':' . $subjectId)?->first();
                    $secondTotal = (float) ($second?->calculated_total ?? $second?->marks_obtained ?? 0);
                    $grandTotal += round(($firstTotal + $secondTotal) / 2, 2);
                } else {
                    $grandTotal += $firstTotal;
                }
            }

            $scores[] = [
                'student_id' => $classStudent->id,
                'grand_total' => round($grandTotal, 2),
                'percentage' => $this->percentage($grandTotal, $maxMarks),
            ];
        }

        usort($scores, static function (array $left, array $right) {
            return [$right['grand_total'], $right['percentage']] <=> [$left['grand_total'], $left['percentage']];
        });

        $rank = 0;
        $position = 0;
        $lastScore = null;

        foreach ($scores as $score) {
            $position++;

            if ($lastScore === null || $score['grand_total'] !== $lastScore['grand_total'] || $score['percentage'] !== $lastScore['percentage']) {
                $rank = $position;
            }

            if ($score['student_id'] === $student->id) {
                return $rank;
            }

            $lastScore = $score;
        }

        return null;
    }

    public function findSemesterExam(int $academicYearId, string $template): ?Exam
    {
        return Exam::query()
            ->where('academic_year_id', $academicYearId)
            ->get()
            ->first(fn (Exam $exam) => $exam->resolved_template === $template);
    }

    private function percentage(float $grandTotal, int|float $maxMarks): float
    {
        return $maxMarks > 0 ? round(($grandTotal / $maxMarks) * 100, 2) : 0.0;
    }
}


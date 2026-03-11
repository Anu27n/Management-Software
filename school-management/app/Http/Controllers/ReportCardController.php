<?php

namespace App\Http\Controllers;

use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SchoolClass;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

class ReportCardController extends Controller
{
    public function exams()
    {
        $exams = Exam::with('academicYear')->latest()->paginate(20);
        $academicYears = AcademicYear::all();
        return view('reportcards.exams', compact('exams', 'academicYears'));
    }

    public function storeExam(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'academic_year_id' => 'required|exists:academic_years,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        Exam::create($validated);
        return redirect()->route('reportcards.exams')->with('success', 'Exam created.');
    }

    public function destroyExam(Exam $exam)
    {
        $exam->delete();
        return redirect()->route('reportcards.exams')->with('success', 'Exam deleted.');
    }

    public function enterMarks(Request $request)
    {
        $exams = Exam::all();
        $classes = SchoolClass::with('sections')->get();
        $students = collect();
        $subjects = collect();
        $sections = collect();
        $existingResults = collect();
        $selectedExam = null;
        $selectedSubject = null;

        if ($request->filled('class_id')) {
            $sections = \App\Models\Section::where('class_id', $request->class_id)->get();
            $subjects = Subject::where('class_id', $request->class_id)->get();
        }

        if ($request->filled(['exam_id', 'class_id', 'section_id', 'subject_id'])) {
            $selectedExam = Exam::find($request->exam_id);
            $selectedSubject = Subject::find($request->subject_id);

            $students = Student::where('class_id', $request->class_id)
                ->where('section_id', $request->section_id)
                ->where('status', 'active')
                ->orderBy('first_name')
                ->get();

            $existingResults = ExamResult::where('exam_id', $request->exam_id)
                ->where('subject_id', $request->subject_id)
                ->whereIn('student_id', $students->pluck('id'))
                ->get()
                ->keyBy('student_id');
        }

        return view('reportcards.enter-marks', compact('exams', 'classes', 'students', 'subjects', 'sections', 'existingResults', 'selectedExam', 'selectedSubject'));
    }

    public function storeMarks(Request $request)
    {
        $request->validate([
            'exam_id' => 'required|exists:exams,id',
            'subject_id' => 'required|exists:subjects,id',
            'results' => 'required|array',
        ]);

        foreach ($request->results as $studentId => $data) {
            if (!empty($data['marks_obtained']) && !empty($data['max_marks'])) {
                $percentage = ($data['marks_obtained'] / $data['max_marks']) * 100;
                $grade = $this->calculateGrade($percentage);

                $student = Student::find($studentId);

                ExamResult::updateOrCreate(
                    [
                        'exam_id' => $request->exam_id,
                        'student_id' => $studentId,
                        'subject_id' => $request->subject_id,
                    ],
                    [
                        'class_id' => $student->class_id,
                        'marks_obtained' => $data['marks_obtained'],
                        'total_marks' => $data['max_marks'],
                        'grade' => $grade,
                    ]
                );
            }
        }

        return redirect()->back()->with('success', 'Marks saved successfully.');
    }

    public function viewReportCard(Request $request)
    {
        $exams = Exam::all();
        $students = Student::where('status', 'active')->orderBy('first_name')->get();
        $student = null;
        $results = collect();
        $selectedExam = null;

        if ($request->filled('student_id') && $request->filled('exam_id')) {
            $selectedExam = Exam::find($request->exam_id);
            $student = Student::with(['schoolClass', 'section', 'academicYear'])->find($request->student_id);
            $results = ExamResult::with('subject')
                ->where('exam_id', $request->exam_id)
                ->where('student_id', $request->student_id)
                ->get();
        }

        return view('reportcards.view', compact('exams', 'students', 'student', 'results', 'selectedExam'));
    }

    private function calculateGrade(float $percentage): string
    {
        return match(true) {
            $percentage >= 90 => 'A+',
            $percentage >= 80 => 'A',
            $percentage >= 70 => 'B+',
            $percentage >= 60 => 'B',
            $percentage >= 50 => 'C',
            $percentage >= 40 => 'D',
            default => 'F',
        };
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Exam;
use App\Models\ExamResult;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentExamReport;
use App\Support\MarksheetBuilder;
use App\Support\ReportTemplateRegistry;
use App\Support\ReportCardSubjectResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportCardController extends Controller
{
    public function __construct(
        private ReportCardSubjectResolver $subjectResolver = new ReportCardSubjectResolver(),
        private MarksheetBuilder $marksheetBuilder = new MarksheetBuilder(),
    ) {
    }

    public function exams()
    {
        return redirect()->route('reportcards.enter-marks');
    }

    public function storeExam(Request $request)
    {
        return redirect()->route('reportcards.enter-marks');
    }

    public function destroyExam(Exam $exam)
    {
        return redirect()->route('reportcards.enter-marks');
    }

    public function enterMarks(Request $request)
    {
        $this->ensureCanManageReportCards();

        $academicYears = AcademicYear::orderByDesc('is_active')->orderByDesc('id')->get();
        $classes = SchoolClass::with('sections')->orderBy('numeric_name')->orderBy('name')->get();
        $sections = collect();
        $students = collect();
        $subjects = collect();
        $gradingSubjects = collect();
        $selectedExam = null;
        $selectedTemplate = $request->input('report_template', 'semester_1');
        $selectedAcademicYear = $request->input('academic_year_id');
        $selectedStudent = null;
        $existingResults = collect();
        $existingReport = null;
        $marksheetPreview = null;
        $promoteClasses = SchoolClass::orderBy('numeric_name')->orderBy('name')->get();

        if ($request->filled('class_id')) {
            $sections = Section::where('class_id', $request->class_id)->orderBy('name')->get();
            $class = SchoolClass::find($request->class_id);

            if ($class) {
                $subjects = $this->subjectResolver->getScholasticSubjects($class);
                $gradingSubjects = $this->subjectResolver->getGradingSubjects($class);
            }
        }

        if ($request->filled(['class_id', 'section_id'])) {
            $students = Student::query()
                ->where('class_id', $request->class_id)
                ->where('section_id', $request->section_id)
                ->where('status', 'active')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name', 'admission_no', 'section_id']);
        }

        if ($request->filled(['academic_year_id', 'class_id', 'section_id', 'student_id', 'report_template'])) {
            $selectedExam = $this->resolveTemplateExam((int) $request->academic_year_id, $request->report_template);
            $selectedStudent = Student::with(['schoolClass', 'section', 'academicYear', 'profile'])->findOrFail($request->student_id);

            abort_if((int) $selectedStudent->class_id !== (int) $request->class_id || (int) $selectedStudent->section_id !== (int) $request->section_id, 422, 'Selected student does not belong to the chosen class and section.');

            $subjectIds = $subjects->pluck('id')->merge($gradingSubjects->pluck('id'));
            $existingResults = ExamResult::query()
                ->where('exam_id', $selectedExam->id)
                ->where('student_id', $selectedStudent->id)
                ->whereIn('subject_id', $subjectIds)
                ->get()
                ->keyBy('subject_id');

            $existingReport = StudentExamReport::where('exam_id', $selectedExam->id)
                ->where('student_id', $selectedStudent->id)
                ->first();

            $marksheetPreview = $this->marksheetBuilder->build($selectedStudent, $selectedExam);
        }

        return view('reportcards.enter-marks', compact(
            'academicYears',
            'classes',
            'sections',
            'students',
            'subjects',
            'gradingSubjects',
            'selectedExam',
            'selectedStudent',
            'existingResults',
            'existingReport',
            'marksheetPreview',
            'promoteClasses',
            'selectedTemplate',
            'selectedAcademicYear'
        ));
    }

    public function storeMarks(Request $request)
    {
        $this->ensureCanManageReportCards();

        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'report_template' => ['required', Rule::in(ReportTemplateRegistry::keys())],
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'student_id' => 'required|exists:students,id',
            'scholastic' => 'required|array',
            'scholastic.*.unit_test_marks' => 'nullable|numeric|min:0|max:20',
            'scholastic.*.main_exam_marks' => 'nullable|numeric|min:0|max:80',
            'grading' => 'nullable|array',
            'grading.*.grade' => ['nullable', Rule::in(['A+', 'A', 'B+', 'B', 'C', 'D', 'Excellent', 'Good', 'Average'])],
            'personal_attributes' => 'nullable|array',
            'personal_attributes.*' => ['nullable', Rule::in(['A', 'B', 'C', 'D', 'Excellent', 'Good', 'Average'])],
            'remarks_unit_test' => 'nullable|string',
            'remarks_main_exam' => 'nullable|string',
            'final_result' => ['nullable', Rule::in(['promoted', 'detained'])],
            'promoted_to_class_id' => 'nullable|exists:classes,id',
            'school_reopens_on' => 'nullable|date',
            'school_timings' => 'nullable|string|max:255',
            'class_teacher_signature' => 'nullable|string|max:255',
            'principal_signature' => 'nullable|string|max:255',
            'parent_signature' => 'nullable|string|max:255',
        ]);

        $exam = $this->resolveTemplateExam((int) $validated['academic_year_id'], $validated['report_template']);
        $student = Student::with('schoolClass')->findOrFail($validated['student_id']);

        abort_if((int) $student->class_id !== (int) $validated['class_id'] || (int) $student->section_id !== (int) $validated['section_id'], 422, 'Selected student does not belong to the chosen class and section.');

        $scholasticSubjects = $this->subjectResolver->getScholasticSubjects($student->schoolClass)->keyBy('id');
        $gradingSubjects = $this->subjectResolver->getGradingSubjects($student->schoolClass)->keyBy('id');

        foreach ($validated['scholastic'] as $subjectId => $marks) {
            if (!$scholasticSubjects->has((int) $subjectId)) {
                continue;
            }

            $unitTest = (float) ($marks['unit_test_marks'] ?? 0);
            $mainExam = (float) ($marks['main_exam_marks'] ?? 0);
            $total = round($unitTest + $mainExam, 2);

            ExamResult::updateOrCreate(
                [
                    'exam_id' => $exam->id,
                    'student_id' => $student->id,
                    'subject_id' => (int) $subjectId,
                ],
                [
                    'class_id' => $student->class_id,
                    'marks_obtained' => $total,
                    'total_marks' => 100,
                    'grade' => $this->calculateGrade($total),
                    'remarks' => null,
                    'unit_test_marks' => $unitTest,
                    'main_exam_marks' => $mainExam,
                    'calculated_total' => $total,
                    'subject_category' => 'scholastic',
                ]
            );
        }

        foreach ($validated['grading'] ?? [] as $subjectId => $gradingData) {
            if (!$gradingSubjects->has((int) $subjectId)) {
                continue;
            }

            $grade = $gradingData['grade'] ?? null;

            if (!filled($grade)) {
                ExamResult::where('exam_id', $exam->id)
                    ->where('student_id', $student->id)
                    ->where('subject_id', (int) $subjectId)
                    ->delete();
                continue;
            }

            ExamResult::updateOrCreate(
                [
                    'exam_id' => $exam->id,
                    'student_id' => $student->id,
                    'subject_id' => (int) $subjectId,
                ],
                [
                    'class_id' => $student->class_id,
                    'marks_obtained' => 0,
                    'total_marks' => 0,
                    'grade' => $grade,
                    'remarks' => null,
                    'unit_test_marks' => null,
                    'main_exam_marks' => null,
                    'calculated_total' => null,
                    'subject_category' => 'grading',
                ]
            );
        }

        StudentExamReport::updateOrCreate(
            [
                'exam_id' => $exam->id,
                'student_id' => $student->id,
            ],
            [
                'class_id' => $student->class_id,
                'section_id' => $student->section_id,
                'remarks_unit_test' => $validated['remarks_unit_test'] ?? null,
                'remarks_main_exam' => $validated['remarks_main_exam'] ?? null,
                'personal_attributes' => array_filter($validated['personal_attributes'] ?? [], fn ($value) => filled($value)),
                'final_result' => $exam->resolved_template === 'semester_2' ? ($validated['final_result'] ?? null) : null,
                'promoted_to_class_id' => $exam->resolved_template === 'semester_2' ? ($validated['promoted_to_class_id'] ?? null) : null,
                'school_reopens_on' => $exam->resolved_template === 'semester_2' ? ($validated['school_reopens_on'] ?? null) : null,
                'school_timings' => $exam->resolved_template === 'semester_2' ? ($validated['school_timings'] ?? null) : null,
                'class_teacher_signature' => $validated['class_teacher_signature'] ?? 'Class Teacher',
                'principal_signature' => $validated['principal_signature'] ?? 'Principal',
                'parent_signature' => $validated['parent_signature'] ?? 'Parent',
            ]
        );

        return redirect()->route('reportcards.enter-marks', [
            'academic_year_id' => $validated['academic_year_id'],
            'report_template' => $validated['report_template'],
            'class_id' => $student->class_id,
            'section_id' => $student->section_id,
            'student_id' => $student->id,
        ])->with('success', 'Marksheet saved successfully.');
    }

    public function classLookups(SchoolClass $class): JsonResponse
    {
        $this->ensureCanManageReportCards();

        return response()->json([
            'sections' => $class->sections()->orderBy('name')->get(['id', 'name']),
            'subjects' => $this->subjectResolver->getScholasticSubjects($class)->map(fn ($subject) => ['id' => $subject->id, 'name' => $subject->name]),
            'students' => Student::query()
                ->where('class_id', $class->id)
                ->where('status', 'active')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name', 'admission_no', 'section_id'])
                ->map(fn (Student $student) => [
                    'id' => $student->id,
                    'name' => $student->full_name,
                    'admission_no' => $student->admission_no,
                    'section_id' => $student->section_id,
                ]),
        ]);
    }

    public function viewReportCard(Request $request)
    {
        $user = auth()->user();
        $academicYears = AcademicYear::orderByDesc('is_active')->orderByDesc('id')->get();
        $students = Student::query()->where('status', 'active');

        if ($user->isParent()) {
            $students->where('parent_user_id', $user->id);
        }

        if ($user->isStudent()) {
            $students->where('email', $user->email);
        }

        $students = $students->orderBy('first_name')->orderBy('last_name')->get();
        $student = null;
        $selectedExam = null;
        $selectedTemplate = $request->input('report_template', 'semester_1');
        $selectedAcademicYear = $request->input('academic_year_id');
        $marksheet = null;

        if ($request->filled('student_id') && $request->filled('academic_year_id') && $request->filled('report_template')) {
            abort_unless($students->contains('id', (int) $request->student_id), 403, 'Unauthorized.');

            $selectedExam = $this->resolveTemplateExam((int) $request->academic_year_id, $request->report_template);
            $student = Student::with(['schoolClass', 'section', 'academicYear', 'profile'])->findOrFail($request->student_id);
            $marksheet = $this->marksheetBuilder->build($student, $selectedExam);
        }

        return view('reportcards.view', compact('academicYears', 'students', 'student', 'selectedExam', 'marksheet', 'selectedTemplate', 'selectedAcademicYear'));
    }

    private function calculateGrade(float $percentage): string
    {
        return match (true) {
            $percentage >= 90 => 'A+',
            $percentage >= 80 => 'A',
            $percentage >= 70 => 'B+',
            $percentage >= 60 => 'B',
            $percentage >= 50 => 'C',
            $percentage >= 40 => 'D',
            default => 'F',
        };
    }

    private function ensureCanManageReportCards(): void
    {
        $user = auth()->user();
        abort_unless($user && $user->hasPermission('reportcards.manage'), 403, 'Unauthorized.');
    }

    private function resolveTemplateExam(int $academicYearId, string $template): Exam
    {
        $meta = ReportTemplateRegistry::meta($template);

        return Exam::firstOrCreate(
            [
                'academic_year_id' => $academicYearId,
                'report_template' => $template,
            ],
            [
                'name' => $meta['exam_name'],
                'term_number' => $meta['term_number'],
            ]
        );
    }
}

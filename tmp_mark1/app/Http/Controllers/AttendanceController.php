<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $classes = SchoolClass::with('sections')->get();
        $date = $request->get('date', today()->format('Y-m-d'));
        $students = collect();
        $attendances = collect();

        if ($request->filled('class_id') && $request->filled('section_id')) {
            $students = Student::where('class_id', $request->class_id)
                ->where('section_id', $request->section_id)
                ->where('status', 'active')
                ->orderBy('first_name')
                ->get();

            $attendances = Attendance::where('class_id', $request->class_id)
                ->where('section_id', $request->section_id)
                ->where('date', $date)
                ->pluck('status', 'student_id');
        }

        return view('attendance.index', compact('classes', 'students', 'attendances', 'date'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'date' => 'required|date',
            'attendance' => 'required|array',
            'attendance.*' => 'in:present,absent,late,half_day',
        ]);

        $academicYear = AcademicYear::current();

        foreach ($request->attendance as $studentId => $status) {
            Attendance::updateOrCreate(
                [
                    'student_id' => $studentId,
                    'date' => $request->date,
                ],
                [
                    'class_id' => $request->class_id,
                    'section_id' => $request->section_id,
                    'academic_year_id' => $academicYear?->id,
                    'status' => $status,
                    'marked_by' => auth()->id(),
                ]
            );
        }

        return redirect()->back()->with('success', 'Attendance saved successfully.');
    }

    public function report(Request $request)
    {
        $classes = SchoolClass::with('sections')->get();
        $report = collect();

        if ($request->filled(['class_id', 'section_id', 'month'])) {
            $month = $request->month;
            $students = Student::where('class_id', $request->class_id)
                ->where('section_id', $request->section_id)
                ->where('status', 'active')
                ->get();

            foreach ($students as $student) {
                $attendances = Attendance::where('student_id', $student->id)
                    ->whereYear('date', substr($month, 0, 4))
                    ->whereMonth('date', substr($month, 5, 2))
                    ->get();

                $report->push([
                    'student' => $student,
                    'present' => $attendances->where('status', 'present')->count(),
                    'absent' => $attendances->where('status', 'absent')->count(),
                    'late' => $attendances->where('status', 'late')->count(),
                    'half_day' => $attendances->where('status', 'half_day')->count(),
                    'total' => $attendances->count(),
                ]);
            }
        }

        return view('attendance.report', compact('classes', 'report'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\FeePayment;
use App\Models\Attendance;
use App\Models\ExamResult;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    // ─── Students ───────────────────────────────────
    public function studentsCSV(Request $request)
    {
        $students = $this->getFilteredStudents($request);

        return $this->streamCsv('students.csv', [
            'Admission No', 'First Name', 'Last Name', 'Gender', 'DOB', 'Class', 'Section',
            'Father Name', 'Phone', 'Email', 'Status'
        ], $students, function ($s) {
            return [
                $s->admission_no, $s->first_name, $s->last_name, $s->gender,
                $s->date_of_birth?->format('Y-m-d'), $s->schoolClass->name ?? '',
                $s->section->name ?? '', $s->father_name, $s->phone, $s->email, $s->status,
            ];
        });
    }

    public function studentsPDF(Request $request)
    {
        $students = $this->getFilteredStudents($request);
        $pdf = Pdf::loadView('exports.students-pdf', compact('students'))->setPaper('a4', 'landscape');
        return $pdf->download('students.pdf');
    }

    // ─── Fee Payments ───────────────────────────────
    public function paymentsCSV(Request $request)
    {
        $payments = $this->getFilteredPayments($request);

        return $this->streamCsv('fee-payments.csv', [
            'Receipt No', 'Student', 'Category', 'Amount Paid', 'Discount', 'Fine',
            'Payment Date', 'Method', 'Status'
        ], $payments, function ($p) {
            return [
                $p->receipt_no,
                $p->student->full_name ?? '',
                $p->feeStructure->feeCategory->name ?? '',
                $p->amount_paid, $p->discount, $p->fine,
                $p->payment_date?->format('Y-m-d'),
                $p->payment_method, $p->status,
            ];
        });
    }

    public function paymentsPDF(Request $request)
    {
        $payments = $this->getFilteredPayments($request);
        $pdf = Pdf::loadView('exports.payments-pdf', compact('payments'))->setPaper('a4', 'landscape');
        return $pdf->download('fee-payments.pdf');
    }

    // ─── Attendance ─────────────────────────────────
    public function attendanceCSV(Request $request)
    {
        $request->validate(['class_id' => 'required', 'section_id' => 'required', 'month' => 'required']);

        $data = $this->getAttendanceData($request);
        $month = $request->month;
        $daysInMonth = \Carbon\Carbon::parse($month . '-01')->daysInMonth;

        $headers = ['#', 'Student'];
        for ($d = 1; $d <= $daysInMonth; $d++) $headers[] = $d;
        $headers[] = 'Present %';

        return $this->streamCsv('attendance-' . $month . '.csv', $headers, $data, function ($row) use ($daysInMonth) {
            $line = [$row['num'], $row['name']];
            for ($d = 1; $d <= $daysInMonth; $d++) {
                $line[] = $row['days'][$d] ?? '-';
            }
            $line[] = $row['percentage'] . '%';
            return $line;
        });
    }

    // ─── Report Card PDF ────────────────────────────
    public function reportCardPDF(Request $request)
    {
        $request->validate(['exam_id' => 'required', 'student_id' => 'required']);

        $student = Student::with(['schoolClass', 'section', 'academicYear'])->findOrFail($request->student_id);
        $results = ExamResult::with('subject')
            ->where('exam_id', $request->exam_id)
            ->where('student_id', $request->student_id)
            ->get();

        $exam = \App\Models\Exam::findOrFail($request->exam_id);

        $pdf = Pdf::loadView('exports.reportcard-pdf', compact('student', 'results', 'exam'));
        return $pdf->download('reportcard-' . $student->admission_no . '.pdf');
    }

    // ─── Helpers ────────────────────────────────────
    private function getFilteredStudents(Request $request)
    {
        $q = Student::with(['schoolClass', 'section']);
        if ($request->filled('class_id')) $q->where('class_id', $request->class_id);
        if ($request->filled('status')) $q->where('status', $request->status);
        return $q->orderBy('first_name')->get();
    }

    private function getFilteredPayments(Request $request)
    {
        $q = FeePayment::with(['student', 'feeStructure.feeCategory']);
        if ($request->filled('status')) $q->where('status', $request->status);
        if ($request->filled('date_from')) $q->whereDate('payment_date', '>=', $request->date_from);
        if ($request->filled('date_to')) $q->whereDate('payment_date', '<=', $request->date_to);
        return $q->latest()->get();
    }

    private function getAttendanceData(Request $request): array
    {
        $month = $request->month;
        $students = Student::where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->where('status', 'active')
            ->orderBy('first_name')->get();

        $daysInMonth = \Carbon\Carbon::parse($month . '-01')->daysInMonth;
        $data = [];
        $i = 0;

        foreach ($students as $student) {
            $i++;
            $attendances = Attendance::where('student_id', $student->id)
                ->whereYear('date', substr($month, 0, 4))
                ->whereMonth('date', substr($month, 5, 2))
                ->get()->keyBy(fn($a) => (int) $a->date->format('d'));

            $days = [];
            $present = 0;
            $total = 0;
            for ($d = 1; $d <= $daysInMonth; $d++) {
                if (isset($attendances[$d])) {
                    $status = $attendances[$d]->status;
                    $days[$d] = strtoupper(substr($status, 0, 1));
                    $total++;
                    if (in_array($status, ['present', 'late'])) $present++;
                } else {
                    $days[$d] = '-';
                }
            }

            $data[] = [
                'num' => $i,
                'name' => $student->full_name,
                'days' => $days,
                'percentage' => $total > 0 ? round(($present / $total) * 100, 1) : 0,
            ];
        }

        return $data;
    }

    private function streamCsv(string $filename, array $headers, $rows, callable $mapper): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows, $mapper) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                fputcsv($handle, $mapper($row));
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}

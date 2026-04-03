<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentWithdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentWithdrawalController extends Controller
{
    public function index(Request $request)
    {
        $studentQuery = Student::query()
            ->with(['schoolClass:id,name', 'section:id,name', 'profile', 'withdrawal'])
            ->where('status', '!=', 'graduated');

        if ($request->filled('class_search')) {
            $classSearch = trim((string) $request->input('class_search'));

            $studentQuery->whereHas('schoolClass', function ($query) use ($classSearch) {
                $query->where('name', 'like', "%{$classSearch}%");
            });
        }

        if ($request->filled('section_search')) {
            $sectionSearch = trim((string) $request->input('section_search'));

            $studentQuery->whereHas('section', function ($query) use ($sectionSearch) {
                $query->where('name', 'like', "%{$sectionSearch}%");
            });
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $fullNameExpression = DB::connection()->getDriverName() === 'sqlite'
                ? "(COALESCE(first_name, '') || ' ' || COALESCE(last_name, ''))"
                : "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))";

            $studentQuery->where(function ($query) use ($search, $fullNameExpression) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhereRaw($fullNameExpression . ' LIKE ?', ["%{$search}%"])
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('father_phone', 'like', "%{$search}%")
                    ->orWhereHas('profile', function ($profileQuery) use ($search) {
                        $profileQuery->where('father_mobile_number', 'like', "%{$search}%")
                            ->orWhere('mother_mobile_number', 'like', "%{$search}%")
                            ->orWhere('phone_number', 'like', "%{$search}%");
                    });
            });
        }

        $students = $studentQuery
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate(15)
            ->withQueryString();

        $selectedStudent = null;
        if ($request->filled('student_id')) {
            $selectedStudent = Student::query()
                ->with(['schoolClass:id,name', 'section:id,name', 'profile', 'withdrawal'])
                ->find($request->input('student_id'));
        }

        $recentWithdrawals = StudentWithdrawal::query()
            ->with(['student:id,first_name,last_name,admission_no,class_id,section_id', 'student.schoolClass:id,name', 'student.section:id,name'])
            ->latest('withdrawal_date')
            ->paginate(10, ['*'], 'history_page');

        return view('students.withdrawals.index', [
            'students' => $students,
            'selectedStudent' => $selectedStudent,
            'recentWithdrawals' => $recentWithdrawals,
            'paymentModes' => ['cash', 'upi', 'bank_transfer', 'cheque'],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'withdrawal_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:2000'],
            'tc_issued' => ['required', 'boolean'],
            'tc_number' => ['nullable', 'string', 'max:100'],
            'tc_date' => ['nullable', 'date'],
            'security_refunded' => ['required', 'boolean'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'refund_date' => ['nullable', 'date'],
            'payment_mode' => ['nullable', 'in:cash,upi,bank_transfer,cheque'],
            'utr_number' => ['nullable', 'string', 'max:100'],
            'cheque_number' => ['nullable', 'string', 'max:100'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $student = Student::query()->with('profile', 'withdrawal')->findOrFail($validated['student_id']);

        $withdrawal = StudentWithdrawal::updateOrCreate(
            ['student_id' => $student->id],
            [
                'withdrawal_date' => $validated['withdrawal_date'],
                'reason' => $validated['reason'],
                'tc_issued' => (bool) $validated['tc_issued'],
                'tc_number' => $request->boolean('tc_issued') ? ($validated['tc_number'] ?? null) : null,
                'tc_date' => $request->boolean('tc_issued') ? ($validated['tc_date'] ?? null) : null,
                'security_refunded' => (bool) $validated['security_refunded'],
                'security_amount' => $student->profile?->security_amount,
                'security_receipt_number' => $student->profile?->security_receipt_number,
                'refund_amount' => $request->boolean('security_refunded') ? ($validated['refund_amount'] ?? $student->profile?->security_amount) : null,
                'refund_date' => $request->boolean('security_refunded') ? ($validated['refund_date'] ?? null) : null,
                'payment_mode' => $request->boolean('security_refunded') ? ($validated['payment_mode'] ?? null) : null,
                'utr_number' => $validated['utr_number'] ?? null,
                'cheque_number' => $validated['cheque_number'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'processed_by' => auth()->id(),
            ]
        );

        $student->update(['status' => 'transferred']);

        return redirect()
            ->route('students.withdrawals.index', ['student_id' => $withdrawal->student_id])
            ->with('success', 'Student withdrawal saved successfully.');
    }
}

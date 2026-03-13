<?php

namespace App\Http\Controllers;

use App\Models\LeaveApplication;
use App\Models\Student;
use App\Models\SchoolClass;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = LeaveApplication::with(['student', 'schoolClass', 'section', 'appliedBy']);

        if ($user->isParent()) {
            $query->whereHas('student', function ($q) use ($user) {
                $q->where('parent_user_id', $user->id);
            });
        }

        if ($user->isStudent()) {
            $query->whereHas('student', function ($q) use ($user) {
                $q->where('email', $user->email);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        $leaves = $query->latest()->paginate(20);
        $classes = SchoolClass::all();

        return view('leaves.index', compact('leaves', 'classes'));
    }

    public function create()
    {
        $user = auth()->user();

        $students = Student::where('status', 'active')->with(['schoolClass', 'section']);
        if ($user->isParent()) {
            $students->where('parent_user_id', $user->id);
        }
        if ($user->isStudent()) {
            $students->where('email', $user->email);
        }

        $students = $students->get();

        return view('leaves.create', compact('students'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'reason' => 'required|string|max:255',
            'description' => 'nullable|string',
            'attachment' => 'nullable|file|max:5120',
        ]);

        $student = Student::findOrFail($validated['student_id']);

        $this->ensureCanAccessStudent($student);

        $validated['class_id'] = $student->class_id;
        $validated['section_id'] = $student->section_id;
        $validated['applied_by'] = auth()->id();

        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')->store('leaves', 'public');
        }

        LeaveApplication::create($validated);
        return redirect()->route('leaves.index')->with('success', 'Leave application submitted.');
    }

    public function show(LeaveApplication $leaf)
    {
        $this->ensureCanAccessStudent($leaf->student);

        $leaf->load(['student', 'schoolClass', 'section', 'appliedBy', 'approvedBy']);
        return view('leaves.show', compact('leaf'));
    }

    public function approve(LeaveApplication $leaf)
    {
        $this->ensureCanApproveOrReject();

        $leaf->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'responded_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Leave approved.');
    }

    public function reject(Request $request, LeaveApplication $leaf)
    {
        $this->ensureCanApproveOrReject();

        $request->validate(['admin_remarks' => 'nullable|string']);

        $leaf->update([
            'status' => 'rejected',
            'admin_remarks' => $request->admin_remarks,
            'approved_by' => auth()->id(),
            'responded_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Leave rejected.');
    }

    private function ensureCanApproveOrReject(): void
    {
        $user = auth()->user();
        abort_unless($user && $user->hasPermission('leaves.approve'), 403, 'Unauthorized.');
    }

    private function ensureCanAccessStudent(?Student $student): void
    {
        if (!$student) {
            abort(404);
        }

        $user = auth()->user();
        if ($user && $user->hasPermission('leaves.approve')) {
            return;
        }

        if ($user->isParent() && (int) $student->parent_user_id === (int) $user->id) {
            return;
        }

        if ($user->isStudent() && $student->email === $user->email) {
            return;
        }

        abort(403, 'Unauthorized.');
    }
}

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
        $query = LeaveApplication::with(['student', 'schoolClass', 'section', 'appliedBy']);

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
        $students = Student::where('status', 'active')->with(['schoolClass', 'section'])->get();
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
        $leaf->load(['student', 'schoolClass', 'section', 'appliedBy', 'approvedBy']);
        return view('leaves.show', compact('leaf'));
    }

    public function approve(LeaveApplication $leaf)
    {
        $leaf->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'responded_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Leave approved.');
    }

    public function reject(Request $request, LeaveApplication $leaf)
    {
        $request->validate(['admin_remarks' => 'nullable|string']);

        $leaf->update([
            'status' => 'rejected',
            'admin_remarks' => $request->admin_remarks,
            'approved_by' => auth()->id(),
            'responded_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Leave rejected.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Homework;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

class HomeworkController extends Controller
{
    public function index(Request $request)
    {
        $query = Homework::with(['schoolClass', 'section', 'subject', 'assignedBy']);

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        $homeworks = $query->latest()->paginate(20);
        $classes = SchoolClass::all();

        return view('homework.index', compact('homeworks', 'classes'));
    }

    public function create()
    {
        $classes = SchoolClass::with('sections')->get();
        $subjects = Subject::all();
        return view('homework.create', compact('classes', 'subjects'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'assign_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:assign_date',
            'attachment' => 'nullable|file|max:10240',
        ]);

        $validated['assigned_by'] = auth()->id();
        $validated['academic_year_id'] = AcademicYear::current()?->id;

        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')->store('homework', 'public');
        }

        Homework::create($validated);
        return redirect()->route('homework.index')->with('success', 'Homework assigned.');
    }

    public function show(Homework $homework)
    {
        $homework->load(['schoolClass', 'section', 'subject', 'assignedBy', 'submissions.student']);
        return view('homework.show', compact('homework'));
    }

    public function edit(Homework $homework)
    {
        $classes = SchoolClass::with('sections')->get();
        $subjects = Subject::all();
        return view('homework.edit', compact('homework', 'classes', 'subjects'));
    }

    public function update(Request $request, Homework $homework)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'subject_id' => 'required|exists:subjects,id',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'assign_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:assign_date',
            'attachment' => 'nullable|file|max:10240',
        ]);

        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')->store('homework', 'public');
        }

        $homework->update($validated);
        return redirect()->route('homework.index')->with('success', 'Homework updated.');
    }

    public function destroy(Homework $homework)
    {
        $homework->delete();
        return redirect()->route('homework.index')->with('success', 'Homework deleted.');
    }

    public function getSubjects(SchoolClass $class)
    {
        return response()->json($class->subjects);
    }
}

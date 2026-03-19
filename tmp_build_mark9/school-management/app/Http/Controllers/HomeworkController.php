<?php

namespace App\Http\Controllers;

use App\Models\Homework;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Models\AcademicYear;
use App\Models\Student;
use Illuminate\Http\Request;

class HomeworkController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Homework::with(['schoolClass', 'section', 'subject', 'assignedBy']);

        if ($user->isParent() || $user->isStudent()) {
            $linkedStudents = $this->getLinkedStudents();
            $classIds = $linkedStudents->pluck('class_id')->unique()->filter();
            $sectionIds = $linkedStudents->pluck('section_id')->unique()->filter();

            if ($classIds->isEmpty() || $sectionIds->isEmpty()) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('class_id', $classIds)->whereIn('section_id', $sectionIds);
            }
        }

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
        $this->ensureCanManageHomework();
        $classes = SchoolClass::with('sections')->get();
        $subjects = Subject::all();
        return view('homework.create', compact('classes', 'subjects'));
    }

    public function store(Request $request)
    {
        $this->ensureCanManageHomework();

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
        $user = auth()->user();
        if ($user->isParent() || $user->isStudent()) {
            $linkedStudents = $this->getLinkedStudents();
            $allowed = $linkedStudents->contains(function ($student) use ($homework) {
                return (int) $student->class_id === (int) $homework->class_id
                    && (int) $student->section_id === (int) $homework->section_id;
            });

            abort_unless($allowed, 403, 'Unauthorized.');
        }

        $homework->load(['schoolClass', 'section', 'subject', 'assignedBy', 'submissions.student']);
        return view('homework.show', compact('homework'));
    }

    public function edit(Homework $homework)
    {
        $this->ensureCanManageHomework();
        $classes = SchoolClass::with('sections')->get();
        $subjects = Subject::all();
        return view('homework.edit', compact('homework', 'classes', 'subjects'));
    }

    public function update(Request $request, Homework $homework)
    {
        $this->ensureCanManageHomework();

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
        $this->ensureCanManageHomework();
        $homework->delete();
        return redirect()->route('homework.index')->with('success', 'Homework deleted.');
    }

    public function getSubjects(SchoolClass $class)
    {
        return response()->json($class->subjects);
    }

    private function ensureCanManageHomework(): void
    {
        $user = auth()->user();
        abort_unless($user && $user->hasPermission('homework.manage'), 403, 'Unauthorized.');
    }

    private function getLinkedStudents()
    {
        $user = auth()->user();

        if ($user->isParent()) {
            return Student::where('parent_user_id', $user->id)->get();
        }

        if ($user->isStudent()) {
            return Student::where('email', $user->email)->get();
        }

        return collect();
    }
}

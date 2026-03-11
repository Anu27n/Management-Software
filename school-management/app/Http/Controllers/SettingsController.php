<?php

namespace App\Http\Controllers;

use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function classes()
    {
        $classes = SchoolClass::withCount(['sections', 'students'])->get();
        return view('settings.classes', compact('classes'));
    }

    public function storeClass(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'numeric_name' => 'nullable|string|max:10',
        ]);

        SchoolClass::create($validated);
        return redirect()->route('settings.classes')->with('success', 'Class created.');
    }

    public function destroyClass(SchoolClass $class)
    {
        $class->delete();
        return redirect()->route('settings.classes')->with('success', 'Class deleted.');
    }

    public function sections()
    {
        $sections = Section::with('schoolClass')->get();
        $classes = SchoolClass::all();
        return view('settings.sections', compact('sections', 'classes'));
    }

    public function storeSection(Request $request)
    {
        $validated = $request->validate([
            'class_id' => 'required|exists:classes,id',
            'name' => 'required|string|max:255',
        ]);

        Section::create($validated);
        return redirect()->route('settings.sections')->with('success', 'Section created.');
    }

    public function destroySection(Section $section)
    {
        $section->delete();
        return redirect()->route('settings.sections')->with('success', 'Section deleted.');
    }

    public function subjects()
    {
        $subjects = Subject::with('schoolClass')->get();
        $classes = SchoolClass::all();
        return view('settings.subjects', compact('subjects', 'classes'));
    }

    public function storeSubject(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:20',
            'class_id' => 'required|exists:classes,id',
        ]);

        Subject::create($validated);
        return redirect()->route('settings.subjects')->with('success', 'Subject created.');
    }

    public function destroySubject(Subject $subject)
    {
        $subject->delete();
        return redirect()->route('settings.subjects')->with('success', 'Subject deleted.');
    }

    public function academicYears()
    {
        $academicYears = AcademicYear::latest()->get();
        return view('settings.academic-years', compact('academicYears'));
    }

    public function storeAcademicYear(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        if ($validated['is_active']) {
            AcademicYear::where('is_active', true)->update(['is_active' => false]);
        }

        AcademicYear::create($validated);
        return redirect()->route('settings.academic-years')->with('success', 'Academic year created.');
    }

    public function destroyAcademicYear(AcademicYear $year)
    {
        $year->delete();
        return redirect()->route('settings.academic-years')->with('success', 'Academic year deleted.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\AcademicYear;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::with(['schoolClass', 'section', 'academicYear']);

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('admission_no', 'like', "%{$search}%");
            });
        }

        $students = $query->latest()->paginate(20);
        $classes = SchoolClass::all();
        $sections = Section::all();

        return view('students.index', compact('students', 'classes', 'sections'));
    }

    public function create()
    {
        $classes = SchoolClass::with('sections')->get();
        $academicYears = AcademicYear::all();
        $parentUsers = User::where('role', 'parent')->where('is_active', true)->orderBy('name')->get();

        return view('students.create', compact('classes', 'academicYears', 'parentUsers'));
    }

    public function bulkUploadForm()
    {
        $classes = SchoolClass::orderBy('name')->get();
        $sections = Section::orderBy('name')->get();
        $academicYears = AcademicYear::orderByDesc('is_active')->orderByDesc('start_date')->get();

        return view('students.bulk-upload', compact('classes', 'sections', 'academicYears'));
    }

    public function downloadBulkTemplate()
    {
        $headers = [
            'admission_no',
            'first_name',
            'last_name',
            'gender',
            'date_of_birth',
            'admission_date',
            'father_name',
            'father_phone',
            'email',
            'phone',
            'class_id',
            'section_id',
            'academic_year_id',
            'parent_user_id',
            'parent_email',
            'status',
        ];

        $sample = [
            'ADM-1001',
            'Rahul',
            'Sharma',
            'male',
            '2012-08-15',
            '2025-04-01',
            'Rakesh Sharma',
            '9876543210',
            'rahul.student@example.com',
            '9876500000',
            '1',
            '1',
            '1',
            '',
            'parent@example.com',
            'active',
        ];

        return response()->streamDownload(function () use ($headers, $sample) {
            $output = fopen('php://output', 'w');
            fputcsv($output, $headers);
            fputcsv($output, $sample);
            fclose($output);
        }, 'students-bulk-upload-template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function bulkUploadStore(Request $request)
    {
        $validated = $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt',
            'default_class_id' => 'nullable|exists:classes,id',
            'default_section_id' => 'nullable|exists:sections,id',
            'default_academic_year_id' => 'nullable|exists:academic_years,id',
        ]);

        $handle = fopen($request->file('csv_file')->getRealPath(), 'r');
        if (!$handle) {
            return back()->with('error', 'Could not read CSV file.');
        }

        $headerRow = fgetcsv($handle);
        if (!$headerRow) {
            fclose($handle);
            return back()->with('error', 'CSV file is empty.');
        }

        $normalizedHeaders = array_map(function ($header) {
            $header = strtolower(trim((string) $header));
            return str_replace(' ', '_', $header);
        }, $headerRow);

        $headerMap = [];
        foreach ($normalizedHeaders as $index => $header) {
            if ($header !== '') {
                $headerMap[$header] = $index;
            }
        }

        $requiredColumns = [
            'admission_no',
            'first_name',
            'last_name',
            'gender',
            'date_of_birth',
            'admission_date',
            'father_name',
        ];

        foreach ($requiredColumns as $requiredColumn) {
            if (!array_key_exists($requiredColumn, $headerMap)) {
                fclose($handle);
                return back()->with('error', "Missing required column in CSV: {$requiredColumn}");
            }
        }

        $defaultClassId = $validated['default_class_id'] ?? null;
        $defaultSectionId = $validated['default_section_id'] ?? null;
        $defaultAcademicYearId = $validated['default_academic_year_id']
            ?? AcademicYear::where('is_active', true)->value('id');

        if (!$defaultAcademicYearId && !array_key_exists('academic_year_id', $headerMap)) {
            fclose($handle);
            return back()->with('error', 'No active academic year found. Select a default academic year or include academic_year_id in the CSV.');
        }

        $classCache = SchoolClass::pluck('id')->flip()->all();
        $sectionCache = Section::pluck('id')->flip()->all();
        $sectionClassMap = Section::pluck('class_id', 'id')->all();
        $academicYearCache = AcademicYear::pluck('id')->flip()->all();
        $parentIdCache = User::where('role', 'parent')->pluck('id')->flip()->all();
        $parentEmailMap = User::where('role', 'parent')
            ->whereNotNull('email')
            ->pluck('id', 'email')
            ->mapWithKeys(fn ($id, $email) => [strtolower($email) => $id])
            ->all();

        $created = 0;
        $failed = 0;
        $errorSamples = [];
        $rowNumber = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNumber++;

            $rowHasData = collect($row)->contains(fn ($cell) => trim((string) $cell) !== '');
            if (!$rowHasData) {
                continue;
            }

            $value = function (string $column) use ($headerMap, $row): ?string {
                if (!array_key_exists($column, $headerMap)) {
                    return null;
                }

                return trim((string) ($row[$headerMap[$column]] ?? ''));
            };

            try {
                $admissionNo = $value('admission_no');
                $firstName = $value('first_name');
                $lastName = $value('last_name');
                $gender = strtolower((string) $value('gender'));
                $dateOfBirth = $value('date_of_birth');
                $admissionDate = $value('admission_date');
                $fatherName = $value('father_name');

                $classId = $value('class_id') !== null && $value('class_id') !== ''
                    ? (int) $value('class_id')
                    : (int) $defaultClassId;
                $sectionId = $value('section_id') !== null && $value('section_id') !== ''
                    ? (int) $value('section_id')
                    : (int) $defaultSectionId;
                $academicYearId = $value('academic_year_id') !== null && $value('academic_year_id') !== ''
                    ? (int) $value('academic_year_id')
                    : (int) $defaultAcademicYearId;

                if (!$admissionNo || !$firstName || !$lastName || !$gender || !$dateOfBirth || !$admissionDate || !$fatherName) {
                    throw new \RuntimeException('Required values are missing.');
                }

                if (!in_array($gender, ['male', 'female', 'other'], true)) {
                    throw new \RuntimeException('Gender must be male, female, or other.');
                }

                if (!isset($classCache[$classId])) {
                    throw new \RuntimeException('Invalid class_id.');
                }

                if (!isset($sectionCache[$sectionId])) {
                    throw new \RuntimeException('Invalid section_id.');
                }

                if ((int) ($sectionClassMap[$sectionId] ?? 0) !== (int) $classId) {
                    throw new \RuntimeException('section_id does not belong to class_id.');
                }

                if (!isset($academicYearCache[$academicYearId])) {
                    throw new \RuntimeException('Invalid academic_year_id.');
                }

                if (Student::where('admission_no', $admissionNo)->exists()) {
                    throw new \RuntimeException('Admission number already exists.');
                }

                $email = $value('email');
                if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new \RuntimeException('Invalid email format.');
                }

                $status = strtolower((string) ($value('status') ?: 'active'));
                if (!in_array($status, ['active', 'inactive', 'graduated', 'transferred'], true)) {
                    $status = 'active';
                }

                $parentUserId = null;
                $parentUserIdRaw = $value('parent_user_id');
                if ($parentUserIdRaw !== null && $parentUserIdRaw !== '') {
                    $parentUserId = (int) $parentUserIdRaw;
                    if (!isset($parentIdCache[$parentUserId])) {
                        throw new \RuntimeException('Invalid parent_user_id.');
                    }
                } else {
                    $parentEmail = strtolower((string) ($value('parent_email') ?? ''));
                    if ($parentEmail !== '') {
                        $parentUserId = $parentEmailMap[$parentEmail] ?? null;
                        if (!$parentUserId) {
                            throw new \RuntimeException('parent_email not found in parent accounts.');
                        }
                    }
                }

                Student::create([
                    'admission_no' => $admissionNo,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'gender' => $gender,
                    'date_of_birth' => $dateOfBirth,
                    'admission_date' => $admissionDate,
                    'class_id' => $classId,
                    'section_id' => $sectionId,
                    'academic_year_id' => $academicYearId,
                    'father_name' => $fatherName,
                    'father_phone' => $value('father_phone') ?: null,
                    'phone' => $value('phone') ?: null,
                    'email' => $email ?: null,
                    'status' => $status,
                    'nationality' => $value('nationality') ?: 'Indian',
                    'parent_user_id' => $parentUserId,
                ]);

                $created++;
            } catch (\Throwable $e) {
                $failed++;
                if (count($errorSamples) < 10) {
                    $errorSamples[] = "Row {$rowNumber}: {$e->getMessage()}";
                }
            }
        }

        fclose($handle);

        $successMessage = "Bulk upload completed. {$created} student(s) created.";
        if ($failed > 0) {
            $errorMessage = "{$failed} row(s) failed. " . implode(' | ', $errorSamples);
            return back()->with('success', $successMessage)->with('error', $errorMessage);
        }

        return redirect()->route('students.index')->with('success', $successMessage);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'admission_no' => 'required|unique:students,admission_no',
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|in:male,female,other',
            'date_of_birth' => 'required|date',
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'admission_date' => 'required|date',
            'father_name' => 'required|string|max:255',
            'blood_group' => 'nullable|string|max:10',
            'religion' => 'nullable|string|max:100',
            'caste' => 'nullable|string|max:100',
            'nationality' => 'nullable|string|max:100',
            'mother_tongue' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'photo' => 'nullable|image|max:2048',
            'previous_school' => 'nullable|string|max:255',
            'father_phone' => 'nullable|string|max:20',
            'father_occupation' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'mother_phone' => 'nullable|string|max:20',
            'mother_occupation' => 'nullable|string|max:255',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_phone' => 'nullable|string|max:20',
            'guardian_relation' => 'nullable|string|max:100',
            'parent_user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'parent')),
            ],
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('students', 'public');
        }

        Student::create($validated);

        return redirect()->route('students.index')->with('success', 'Student added successfully.');
    }

    public function show(Student $student)
    {
        $student->load(['schoolClass', 'section', 'academicYear', 'attendances', 'feePayments.feeStructure.feeCategory', 'examResults.exam', 'examResults.subject']);
        return view('students.show', compact('student'));
    }

    public function edit(Student $student)
    {
        $classes = SchoolClass::with('sections')->get();
        $academicYears = AcademicYear::all();
        $parentUsers = User::where('role', 'parent')->orderBy('name')->get();

        return view('students.edit', compact('student', 'classes', 'academicYears', 'parentUsers'));
    }

    public function update(Request $request, Student $student)
    {
        $validated = $request->validate([
            'admission_no' => 'required|unique:students,admission_no,' . $student->id,
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'required|in:male,female,other',
            'date_of_birth' => 'required|date',
            'class_id' => 'required|exists:classes,id',
            'section_id' => 'required|exists:sections,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'admission_date' => 'required|date',
            'father_name' => 'required|string|max:255',
            'blood_group' => 'nullable|string|max:10',
            'religion' => 'nullable|string|max:100',
            'caste' => 'nullable|string|max:100',
            'nationality' => 'nullable|string|max:100',
            'mother_tongue' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'photo' => 'nullable|image|max:2048',
            'previous_school' => 'nullable|string|max:255',
            'father_phone' => 'nullable|string|max:20',
            'father_occupation' => 'nullable|string|max:255',
            'mother_name' => 'nullable|string|max:255',
            'mother_phone' => 'nullable|string|max:20',
            'mother_occupation' => 'nullable|string|max:255',
            'guardian_name' => 'nullable|string|max:255',
            'guardian_phone' => 'nullable|string|max:20',
            'guardian_relation' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,inactive,graduated,transferred',
            'parent_user_id' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'parent')),
            ],
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('students', 'public');
        }

        $student->update($validated);

        return redirect()->route('students.index')->with('success', 'Student updated successfully.');
    }

    public function destroy(Student $student)
    {
        $student->delete();
        return redirect()->route('students.index')->with('success', 'Student deleted successfully.');
    }

    public function getSections(SchoolClass $class)
    {
        return response()->json($class->sections);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\AcademicYear;
use App\Models\NotificationSetting;
use App\Models\StudentProfile;
use App\Models\User;
use App\Http\Requests\StoreComprehensiveStudentRequest;
use App\Support\ClassEligibility;
use App\Support\UserCredentialSupport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class StudentController extends Controller
{
    public function index(Request $request)
    {
        $query = Student::query()
            ->select([
                'id',
                'admission_no',
                'first_name',
                'last_name',
                'father_name',
                'father_phone',
                'phone',
                'class_id',
                'section_id',
                'academic_year_id',
                'status',
                'created_at',
            ])
            ->with([
                'schoolClass:id,name',
                'section:id,name',
                'academicYear:id,name',
            ]);

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

        $students = $query->latest()->paginate(20)->withQueryString();
        $classes = SchoolClass::query()->select(['id', 'name'])->orderBy('name')->get();
        $sections = Section::query()->select(['id', 'name', 'class_id'])->orderBy('name')->get();

        return view('students.index', compact('students', 'classes', 'sections'));
    }

    public function create()
    {
        $classes = SchoolClass::query()
            ->select(['id', 'name'])
            ->with('sections:id,class_id,name')
            ->orderBy('name')
            ->get();
        $academicYears = AcademicYear::query()
            ->select(['id', 'name', 'is_active', 'start_date'])
            ->orderByDesc('is_active')
            ->orderByDesc('start_date')
            ->get();
        $siblingCandidates = Student::query()
            ->with(['schoolClass:id,name', 'section:id,name'])
            ->where('status', 'active')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get(['id', 'admission_no', 'first_name', 'last_name', 'father_name', 'mother_name', 'class_id', 'section_id']);

        return view('students.create', compact('classes', 'academicYears', 'siblingCandidates'));
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

    public function promoteForm(Request $request)
    {
        $classes = SchoolClass::with('sections')->get();
        $academicYears = AcademicYear::orderByDesc('is_active')->orderByDesc('start_date')->get();
        $students = collect();

        if ($request->filled(['source_class_id', 'source_section_id', 'source_academic_year_id'])) {
            $students = Student::with(['schoolClass', 'section', 'academicYear'])
                ->where('class_id', $request->source_class_id)
                ->where('section_id', $request->source_section_id)
                ->where('academic_year_id', $request->source_academic_year_id)
                ->where('status', 'active')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get();
        }

        return view('students.promote', compact('classes', 'academicYears', 'students'));
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

    public function promoteStore(Request $request)
    {
        $validated = $request->validate([
            'source_class_id' => 'required|exists:classes,id',
            'source_section_id' => 'required|exists:sections,id',
            'source_academic_year_id' => 'required|exists:academic_years,id',
            'target_academic_year_id' => 'required|exists:academic_years,id',
            'target_class_id' => 'nullable|exists:classes,id',
            'target_section_id' => 'nullable|exists:sections,id',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'exists:students,id',
            'student_actions' => 'required|array',
        ]);

        $students = Student::query()
            ->whereIn('id', $validated['student_ids'])
            ->where('class_id', $validated['source_class_id'])
            ->where('section_id', $validated['source_section_id'])
            ->where('academic_year_id', $validated['source_academic_year_id'])
            ->get();

        if ($students->isEmpty()) {
            return back()->with('error', 'No matching students were found for promotion.');
        }

        $promoteCount = 0;
        $repeatCount = 0;

        foreach ($students as $student) {
            $action = $validated['student_actions'][$student->id] ?? null;

            if (!in_array($action, ['promote', 'repeat'], true)) {
                continue;
            }

            if ($action === 'promote') {
                if (empty($validated['target_class_id']) || empty($validated['target_section_id'])) {
                    return back()->with('error', 'Select the target class and section for promoted students.');
                }

                $student->update([
                    'academic_year_id' => $validated['target_academic_year_id'],
                    'class_id' => $validated['target_class_id'],
                    'section_id' => $validated['target_section_id'],
                    'status' => 'active',
                ]);
                $promoteCount++;
                continue;
            }

            $student->update([
                'academic_year_id' => $validated['target_academic_year_id'],
                'status' => 'active',
            ]);
            $repeatCount++;
        }

        return redirect()
            ->route('students.index')
            ->with('success', "Promotion completed. Promoted: {$promoteCount}, Repeated: {$repeatCount}.");
    }

    public function store(StoreComprehensiveStudentRequest $request)
    {
        $validated = $request->validated();
        $studentData = $this->mapStudentCoreData($validated);
        $profileData = $this->mapStudentProfileData($validated);

        $generatedCredentials = null;
        $student = null;

        DB::transaction(function () use (&$studentData, &$profileData, &$generatedCredentials, &$student) {
            [$parentUser, $generatedCredentials] = $this->resolveParentAccount($studentData);
            $studentData['parent_user_id'] = $parentUser?->id;

            $student = Student::create($studentData);
            $student->profile()->create($profileData);
        });

        $successMessage = $generatedCredentials
            ? 'Student added and parent login created successfully.'
            : 'Student added successfully.';

        $redirect = redirect()
            ->route('students.create')
            ->with('success', $successMessage);

        if ($generatedCredentials) {
            $redirect->with('generated_credentials', $generatedCredentials);
        }

        return $redirect;
    }

    public function apiIndex(Request $request): JsonResponse
    {
        $students = Student::with(['schoolClass:id,name', 'section:id,name', 'academicYear:id,name', 'profile'])
            ->when($request->filled('class_id'), fn ($query) => $query->where('class_id', $request->input('class_id')))
            ->when($request->filled('section_id'), fn ($query) => $query->where('section_id', $request->input('section_id')))
            ->when($request->filled('academic_year_id'), fn ($query) => $query->where('academic_year_id', $request->input('academic_year_id')))
            ->latest()
            ->paginate((int) $request->input('per_page', 20));

        return response()->json($students);
    }

    public function apiStore(StoreComprehensiveStudentRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $studentData = $this->mapStudentCoreData($validated);
        $profileData = $this->mapStudentProfileData($validated);

        $generatedCredentials = null;

        $student = DB::transaction(function () use (&$studentData, &$profileData, &$generatedCredentials) {
            [$parentUser, $generatedCredentials] = $this->resolveParentAccount($studentData);
            $studentData['parent_user_id'] = $parentUser?->id;

            $created = Student::create($studentData);
            $created->profile()->create($profileData);

            return $created->load(['schoolClass:id,name', 'section:id,name', 'academicYear:id,name', 'profile']);
        });

        return response()->json([
            'message' => 'Student created successfully.',
            'student' => $student,
            'generated_credentials' => $generatedCredentials,
        ], 201);
    }

    public function show(Student $student)
    {
        $student->load([
            'schoolClass:id,name',
            'section:id,name',
            'academicYear:id,name',
            'profile',
            'parentUser:id,name,email,phone,username',
            'attendances:id,student_id,date,status,remarks',
            'feePayments:id,student_id,fee_structure_id,amount_paid,payment_date,status,receipt_no',
            'feePayments.feeStructure:id,fee_category_id',
            'feePayments.feeStructure.feeCategory:id,name',
            'examResults:id,student_id,exam_id,subject_id,marks_obtained,grade',
            'examResults.exam:id,name',
            'examResults.subject:id,name',
        ]);
        return view('students.show', compact('student'));
    }

    public function edit(Student $student)
    {
        $classes = SchoolClass::with('sections')->get();
        $academicYears = AcademicYear::all();
        $student->load('parentUser', 'profile');
        $canEditRte = $this->canEditRte(request()->user());

        return view('students.edit', compact('student', 'classes', 'academicYears', 'canEditRte'));
    }

    public function update(Request $request, Student $student)
    {
        $canEditRte = $this->canEditRte($request->user());

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
            'phone' => 'required|string|max:20',
            'email' => 'required|email|max:255',
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
            'rte' => 'nullable|in:yes,no',
            'status' => 'nullable|in:active,inactive,graduated,transferred',
        ]);

        $className = SchoolClass::query()->whereKey($validated['class_id'])->value('name');
        $isRteEligible = ClassEligibility::isRteEligible($className);
        $existingRte = $student->profile?->rte;
        $incomingRte = $validated['rte'] ?? null;

        if ($canEditRte) {
            $rte = $isRteEligible ? $incomingRte : null;

            if ($isRteEligible && blank($rte)) {
                throw ValidationException::withMessages([
                    'rte' => 'The RTE field is required for classes up to 8th.',
                ]);
            }

            if (! $isRteEligible && filled($incomingRte)) {
                throw ValidationException::withMessages([
                    'rte' => 'The RTE field is only allowed for classes up to 8th.',
                ]);
            }
        } else {
            if ($request->filled('rte') && $incomingRte !== $existingRte) {
                throw ValidationException::withMessages([
                    'rte' => 'You are not allowed to edit RTE.',
                ]);
            }

            $rte = $existingRte;
        }

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('students', 'public');
        }

        $generatedCredentials = null;

        DB::transaction(function () use ($student, &$validated, &$generatedCredentials, $rte) {
            [$parentUser, $generatedCredentials] = $this->resolveParentAccount($validated, $student);
            $validated['parent_user_id'] = $parentUser->id;

            $student->update($validated);

            StudentProfile::updateOrCreate(
                ['student_id' => $student->id],
                [
                    'student_first_name' => $validated['first_name'],
                    'rte' => $rte,
                ]
            );
        });

        $successMessage = $generatedCredentials
            ? 'Student updated and parent login created successfully.'
            : 'Student updated successfully.';

        $redirect = redirect()
            ->route('students.index')
            ->with('success', $successMessage);

        if ($generatedCredentials) {
            $redirect->with('generated_credentials', $generatedCredentials);
        }

        return $redirect;
    }

    private function canEditRte(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasPermission('students.manage');
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

    private function mapStudentCoreData(array $validated): array
    {
        $admissionNo = $this->resolveAdmissionNumber($validated);
        $studentLastName = trim(implode(' ', array_filter([
            trim((string) ($validated['student_middle_name'] ?? '')),
            trim((string) ($validated['student_surname'] ?? '')),
        ], fn ($value) => $value !== '')));
        $primaryPhone = $validated['phone_number']
            ?? $validated['father_mobile_number']
            ?? $validated['mother_mobile_number']
            ?? null;
        $primaryEmail = $validated['father_email']
            ?? $validated['mother_email']
            ?? strtolower($admissionNo . '@school.local');

        return [
            'admission_no' => $admissionNo,
            'first_name' => $validated['student_first_name'],
            'last_name' => $studentLastName,
            'gender' => $validated['gender'],
            'date_of_birth' => $validated['date_of_birth'],
            'blood_group' => $validated['blood_group'] ?? null,
            'caste' => $validated['category'],
            'nationality' => $validated['nationality'],
            'address' => $validated['residential_address'],
            'phone' => filled($primaryPhone) ? $primaryPhone : null,
            'email' => filled($primaryEmail) ? $primaryEmail : strtolower($admissionNo . '@school.local'),
            'admission_date' => $validated['admission_date'],
            'previous_school' => $validated['last_school_name'] ?? null,
            'father_name' => filled($validated['father_name'] ?? null) ? $validated['father_name'] : 'N/A',
            'father_phone' => $validated['father_phone'] ?? null,
            'father_occupation' => $validated['father_occupation'] ?? null,
            'mother_name' => filled($validated['mother_name'] ?? null) ? $validated['mother_name'] : null,
            'mother_phone' => $validated['mother_phone'] ?? null,
            'mother_occupation' => $validated['mother_occupation'] ?? null,
            'guardian_name' => $validated['guardian_name'] ?? null,
            'guardian_phone' => $validated['phone_number'] ?? null,
            'guardian_relation' => null,
            'class_id' => $validated['class_id'],
            'section_id' => $validated['section_id'],
            'academic_year_id' => $validated['academic_year_id'],
            'status' => 'active',
        ];
    }

    private function resolveAdmissionNumber(array $validated): string
    {
        $providedAdmissionNo = trim((string) ($validated['student_s_no'] ?? ''));

        if ($providedAdmissionNo !== '') {
            return $providedAdmissionNo;
        }

        do {
            $generatedAdmissionNo = 'ADM-' . now()->format('Ymd') . '-' . Str::upper(Str::random(4));
        } while (Student::where('admission_no', $generatedAdmissionNo)->exists());

        return $generatedAdmissionNo;
    }

    private function mapStudentProfileData(array $validated): array
    {
        $className = SchoolClass::query()->whereKey($validated['class_id'])->value('name');
        $isRteEligible = ClassEligibility::isRteEligible($className);
        $siblingDetails = $this->buildSiblingDetails($validated);
        $firstSibling = $siblingDetails[0] ?? null;
        $secondSibling = $siblingDetails[1] ?? null;

        return [
            'student_s_no' => filled($validated['student_s_no'] ?? null) ? $validated['student_s_no'] : null,
            'student_surname' => filled($validated['student_surname'] ?? null) ? $validated['student_surname'] : null,
            'student_first_name' => $validated['student_first_name'],
            'student_middle_name' => filled($validated['student_middle_name'] ?? null) ? $validated['student_middle_name'] : null,
            'nationality' => $validated['nationality'],
            'aadhaar_number' => filled($validated['aadhaar_number'] ?? null) ? $validated['aadhaar_number'] : null,
            'student_pen_number' => $validated['student_pen_number'] ?? null,
            'category' => $validated['category'],
            'residential_address' => $validated['residential_address'],
            'father_mobile_number' => $validated['father_mobile_number'],
            'mother_mobile_number' => $validated['mother_mobile_number'],
            'last_school_name' => $validated['last_school_name'] ?? null,
            'last_class' => $validated['last_class'] ?? null,
            'report_card_attached' => (bool) $validated['report_card_attached'],
            'transfer_certificate_attached' => (bool) $validated['transfer_certificate_attached'],
            'is_child_healthy' => $validated['is_child_healthy'] ?? 'yes',
            'health_report_attached' => (bool) ($validated['health_report_attached'] ?? false),
            'father_name' => filled($validated['father_name'] ?? null) ? $validated['father_name'] : null,
            'father_education' => $validated['father_education'] ?? null,
            'father_medium_of_instruction' => $validated['father_medium_of_instruction'] ?? null,
            'father_occupation' => $validated['father_occupation'] ?? null,
            'father_business_designation' => $validated['father_business_designation'] ?? null,
            'father_organization_name' => $validated['father_organization_name'] ?? null,
            'father_office_address' => $validated['father_office_address'] ?? null,
            'father_phone' => $validated['father_phone'] ?? null,
            'father_email' => $validated['father_email'] ?? null,
            'mother_name' => filled($validated['mother_name'] ?? null) ? $validated['mother_name'] : null,
            'mother_education' => $validated['mother_education'] ?? null,
            'mother_medium_of_instruction' => $validated['mother_medium_of_instruction'] ?? null,
            'mother_occupation' => $validated['mother_occupation'] ?? null,
            'mother_business_designation' => $validated['mother_business_designation'] ?? null,
            'mother_organization_name' => $validated['mother_organization_name'] ?? null,
            'mother_office_address' => $validated['mother_office_address'] ?? null,
            'mother_phone' => $validated['mother_phone'] ?? null,
            'mother_email' => $validated['mother_email'] ?? null,
            'house' => $validated['house'] ?? null,
            'blood_group' => $validated['blood_group'] ?? null,
            'height_cm' => $validated['height_cm'] ?? null,
            'weight_kg' => $validated['weight_kg'] ?? null,
            'transport_mode' => $validated['transport_mode'] ?? null,
            'has_guardian' => (bool) ($validated['has_guardian'] ?? false),
            'guardian_name' => $validated['guardian_name'] ?? null,
            'guardian_relation' => $validated['guardian_relation'] ?? null,
            'phone_number' => $validated['phone_number'] ?? null,
            'office_address' => $validated['office_address'] ?? null,
            'father_mobile' => $validated['father_mobile'] ?? null,
            'mother_mobile' => $validated['mother_mobile'] ?? null,
            'has_siblings' => (bool) ($validated['has_siblings'] ?? false),
            'sibling_count' => (int) ($validated['sibling_count'] ?? count($siblingDetails)),
            'sibling_details' => $siblingDetails,
            'sibling_1_name' => $firstSibling['name'] ?? null,
            'sibling_1_class' => $firstSibling['class_id'] ?? null,
            'sibling_2_name' => $secondSibling['name'] ?? null,
            'sibling_2_class' => $secondSibling['class_id'] ?? null,
            'bpl_beneficiary' => $validated['bpl_beneficiary'] ?? 'na',
            'rte' => $isRteEligible ? ($validated['rte'] ?? null) : null,
            'father_signature' => $validated['father_signature'] ?? null,
            'mother_signature' => $validated['mother_signature'] ?? null,
            'guardian_signature' => $validated['guardian_signature'] ?? null,
            'registration_receipt_number' => $validated['registration_receipt_number'] ?? null,
            'registration_amount' => $validated['registration_amount'] ?? null,
            'class_section_allotted' => $validated['class_section_allotted'] ?? null,
            'date_of_admission' => $validated['date_of_admission'] ?? null,
            'fee_booklet_number' => $validated['fee_booklet_number'] ?? null,
            'security_receipt_number' => $validated['security_receipt_number'] ?? null,
            'security_amount' => $validated['security_amount'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
            'principal_signature' => $validated['principal_signature'] ?? null,
            'office_incharge_signature' => $validated['office_incharge_signature'] ?? null,
        ];
    }

    private function resolveParentAccount(array $validated, ?Student $student = null): array
    {
        $email = strtolower(trim((string) ($validated['email'] ?? '')));
        $phone = trim((string) ($validated['phone'] ?? ''));
        $linkedParent = $student?->parentUser;
        $hasUsernameColumn = Schema::hasColumn('users', 'username');

        if ($email === '') {
            return [null, null];
        }

        $existingUser = User::query()
            ->whereRaw('LOWER(email) = ?', [$email])
            ->first();

        if ($existingUser && $existingUser->role !== 'parent' && (!$linkedParent || $existingUser->id !== $linkedParent->id)) {
            throw ValidationException::withMessages([
                'email' => 'This email is already used by another non-parent login.',
            ]);
        }

        if ($linkedParent && (!$existingUser || $existingUser->id === $linkedParent->id)) {
            $updateData = [
                'name' => $this->buildParentAccountName($validated),
                'email' => $email,
                'phone' => $phone,
                'is_active' => true,
            ];

            if ($hasUsernameColumn) {
                $updateData['username'] = $linkedParent->username ?: UserCredentialSupport::generateUniqueUsername($email, $linkedParent->id);
            }

            $linkedParent->update($updateData);

            return [$linkedParent->fresh(), null];
        }

        if ($existingUser) {
            $updateData = [
                'name' => $existingUser->name ?: $this->buildParentAccountName($validated),
                'phone' => $existingUser->phone ?: $phone,
            ];

            if ($hasUsernameColumn && blank($existingUser->username)) {
                $updateData['username'] = UserCredentialSupport::generateUniqueUsername($email, $existingUser->id);
            }

            $existingUser->update($updateData);

            return [$existingUser->fresh(), null];
        }

        $plainPassword = UserCredentialSupport::generateTemporaryPassword();
        $parentUserData = [
            'name' => $this->buildParentAccountName($validated),
            'email' => $email,
            'phone' => $phone !== '' ? $phone : null,
            'role' => 'parent',
            'password' => $plainPassword,
            'is_active' => true,
        ];

        if ($hasUsernameColumn) {
            $parentUserData['username'] = UserCredentialSupport::generateUniqueUsername($email);
        }

        $parentUser = User::create($parentUserData);

        $this->sendAdmissionCredentials($parentUser, $validated, $plainPassword);

        return [
            $parentUser,
            [
                'name' => $parentUser->name,
                'username' => $hasUsernameColumn ? $parentUser->username : $parentUser->email,
                'email' => $parentUser->email,
                'password' => $plainPassword,
                'message' => 'Copy these credentials now. They are shown once and are also emailed when SMTP is configured.',
            ],
        ];
    }

    private function buildParentAccountName(array $validated): string
    {
        $name = trim((string) ($validated['guardian_name']
            ?? $validated['father_name']
            ?? $validated['mother_name']
            ?? ''));

        if ($name !== '') {
            return $name;
        }

        return trim(($validated['student_first_name'] ?? 'Student') . ' ' . ($validated['student_surname'] ?? 'Parent')) . ' Parent';
    }

    private function sendAdmissionCredentials(User $parentUser, array $validated, string $plainPassword): void
    {
        $settings = NotificationSetting::first();
        if (!$settings || !$settings->mail_enabled) {
            return;
        }

        try {
            if ($settings->mail_host && $settings->mail_port && $settings->mail_username) {
                config([
                    'mail.default' => 'smtp',
                    'mail.mailers.smtp.host' => $settings->mail_host,
                    'mail.mailers.smtp.port' => (int) $settings->mail_port,
                    'mail.mailers.smtp.encryption' => $settings->mail_encryption,
                    'mail.mailers.smtp.username' => $settings->mail_username,
                    'mail.mailers.smtp.password' => $settings->mail_password,
                    'mail.from.address' => $settings->mail_from_address ?: config('mail.from.address'),
                    'mail.from.name' => $settings->mail_from_name ?: config('mail.from.name'),
                ]);
            }

            $studentName = trim(($validated['first_name'] ?? '') . ' ' . ($validated['last_name'] ?? ''));

            Mail::raw(
                "A login has been created for {$studentName}.\n\n"
                . "Username: " . ($parentUser->username ?: $parentUser->email) . "\n"
                . "Email: {$parentUser->email}\n"
                . "Password: {$plainPassword}\n"
                . "Login URL: " . url('/') . "\n\n"
                . "Please sign in and change the password after first login.",
                function ($message) use ($parentUser, $studentName) {
                    $message
                        ->to($parentUser->email)
                        ->subject('Your School Portal Login - ' . ($studentName ?: 'Student Admission'));
                }
            );
        } catch (\Throwable $exception) {
            // Do not block admission if email delivery fails on shared hosting.
        }
    }

    private function buildSiblingDetails(array $validated): array
    {
        $rawSiblings = collect($validated['sibling_details'] ?? [])
            ->filter(fn ($item) => is_array($item))
            ->values();
        $linkedSiblingIds = $rawSiblings
            ->pluck('student_id')
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value) => (int) $value)
            ->unique()
            ->values();
        $linkedSiblings = Student::query()
            ->with('schoolClass:id,name')
            ->whereIn('id', $linkedSiblingIds)
            ->get()
            ->keyBy('id');

        return $rawSiblings
            ->map(function (array $item) use ($linkedSiblings) {
                $linkedSibling = filled($item['student_id'] ?? null)
                    ? $linkedSiblings->get((int) $item['student_id'])
                    : null;

                $name = $linkedSibling?->full_name
                    ?: trim((string) ($item['name'] ?? ''));
                $classId = $linkedSibling?->class_id
                    ?: (!empty($item['class_id']) ? (int) $item['class_id'] : null);
                $className = $linkedSibling?->schoolClass?->name;

                if ($name === '') {
                    return null;
                }

                return [
                    'student_id' => $linkedSibling?->id,
                    'admission_no' => $linkedSibling?->admission_no,
                    'name' => $name,
                    'is_studying' => $linkedSibling ? true : !empty($item['is_studying']),
                    'class_id' => $classId,
                    'class_name' => $className,
                    'father_name' => $linkedSibling?->father_name,
                    'mother_name' => $linkedSibling?->mother_name,
                    'notes' => filled($item['notes'] ?? null) ? trim((string) $item['notes']) : null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}


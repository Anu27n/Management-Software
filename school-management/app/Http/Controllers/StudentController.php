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
use App\Support\UserCredentialSupport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

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

        return view('students.create', compact('classes', 'academicYears'));
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
            $studentData['parent_user_id'] = $parentUser->id;

            $student = Student::create($studentData);
            $student->profile()->create($profileData);
        });

        $successMessage = $generatedCredentials
            ? 'Student added and parent login created successfully.'
            : 'Student added successfully. Existing parent login was linked.';

        $redirect = redirect()
            ->route('students.index')
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
            $studentData['parent_user_id'] = $parentUser->id;

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
        $student->load(['schoolClass', 'section', 'academicYear', 'attendances', 'feePayments.feeStructure.feeCategory', 'examResults.exam', 'examResults.subject']);
        return view('students.show', compact('student'));
    }

    public function edit(Student $student)
    {
        $classes = SchoolClass::with('sections')->get();
        $academicYears = AcademicYear::all();
        $student->load('parentUser');

        return view('students.edit', compact('student', 'classes', 'academicYears'));
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
            'status' => 'nullable|in:active,inactive,graduated,transferred',
        ]);

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('students', 'public');
        }

        $generatedCredentials = null;

        DB::transaction(function () use ($student, &$validated, &$generatedCredentials) {
            [$parentUser, $generatedCredentials] = $this->resolveParentAccount($validated, $student);
            $validated['parent_user_id'] = $parentUser->id;

            $student->update($validated);
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
        return [
            'admission_no' => $validated['student_s_no'],
            'first_name' => $validated['student_first_name'],
            'last_name' => trim(($validated['student_middle_name'] ?? '') . ' ' . ($validated['student_surname'] ?? '')) ?: $validated['student_surname'] ?? 'NA',
            'gender' => $validated['gender'],
            'date_of_birth' => $validated['date_of_birth'],
            'blood_group' => $validated['blood_group'] ?? null,
            'caste' => $validated['category'],
            'nationality' => $validated['nationality'],
            'address' => $validated['residential_address'],
            'phone' => $validated['father_mobile_number'],
            'email' => $validated['father_email'] ?? ($validated['mother_email'] ?? strtolower($validated['student_s_no'] . '@school.local')),
            'admission_date' => $validated['admission_date'],
            'previous_school' => $validated['last_school_name'] ?? null,
            'father_name' => $validated['father_name'],
            'father_phone' => $validated['father_phone'] ?? null,
            'father_occupation' => $validated['father_occupation'] ?? null,
            'mother_name' => $validated['mother_name'],
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

    private function mapStudentProfileData(array $validated): array
    {
        return [
            'student_s_no' => $validated['student_s_no'],
            'student_surname' => $validated['student_surname'] ?? null,
            'student_first_name' => $validated['student_first_name'],
            'student_middle_name' => $validated['student_middle_name'] ?? null,
            'nationality' => $validated['nationality'],
            'aadhaar_number' => $validated['aadhaar_number'],
            'student_pen_number' => $validated['student_pen_number'] ?? null,
            'category' => $validated['category'],
            'class_applied_for' => $validated['class_applied_for'],
            'residential_address' => $validated['residential_address'],
            'father_mobile_number' => $validated['father_mobile_number'],
            'mother_mobile_number' => $validated['mother_mobile_number'],
            'last_school_name' => $validated['last_school_name'] ?? null,
            'last_class' => $validated['last_class'] ?? null,
            'report_card_attached' => (bool) $validated['report_card_attached'],
            'transfer_certificate_attached' => (bool) $validated['transfer_certificate_attached'],
            'is_child_healthy' => $validated['is_child_healthy'],
            'health_report_attached' => (bool) $validated['health_report_attached'],
            'father_name' => $validated['father_name'],
            'father_education' => $validated['father_education'] ?? null,
            'father_medium_of_instruction' => $validated['father_medium_of_instruction'] ?? null,
            'father_occupation' => $validated['father_occupation'] ?? null,
            'father_business_designation' => $validated['father_business_designation'] ?? null,
            'father_organization_name' => $validated['father_organization_name'] ?? null,
            'father_office_address' => $validated['father_office_address'] ?? null,
            'father_phone' => $validated['father_phone'] ?? null,
            'father_email' => $validated['father_email'] ?? null,
            'mother_name' => $validated['mother_name'],
            'mother_education' => $validated['mother_education'] ?? null,
            'mother_medium_of_instruction' => $validated['mother_medium_of_instruction'] ?? null,
            'mother_occupation' => $validated['mother_occupation'] ?? null,
            'mother_business_designation' => $validated['mother_business_designation'] ?? null,
            'mother_organization_name' => $validated['mother_organization_name'] ?? null,
            'mother_office_address' => $validated['mother_office_address'] ?? null,
            'mother_phone' => $validated['mother_phone'] ?? null,
            'mother_email' => $validated['mother_email'] ?? null,
            'parent_guardian_signature' => $validated['parent_guardian_signature'],
            'declaration_date' => $validated['declaration_date'],
            'student_name' => $validated['student_name'],
            'personal_record_class' => $validated['personal_record_class'],
            'personal_record_section' => $validated['personal_record_section'],
            'house' => $validated['house'] ?? null,
            'blood_group' => $validated['blood_group'] ?? null,
            'height_cm' => $validated['height_cm'] ?? null,
            'weight_kg' => $validated['weight_kg'] ?? null,
            'transport_mode' => $validated['transport_mode'],
            'guardian_name' => $validated['guardian_name'] ?? null,
            'phone_number' => $validated['phone_number'] ?? null,
            'office_address' => $validated['office_address'] ?? null,
            'father_mobile' => $validated['father_mobile'] ?? null,
            'mother_mobile' => $validated['mother_mobile'] ?? null,
            'sibling_1_name' => $validated['sibling_1_name'] ?? null,
            'sibling_1_class' => $validated['sibling_1_class'] ?? null,
            'sibling_2_name' => $validated['sibling_2_name'] ?? null,
            'sibling_2_class' => $validated['sibling_2_class'] ?? null,
            'bpl_beneficiary' => $validated['bpl_beneficiary'],
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
        $email = strtolower(trim((string) $validated['email']));
        $phone = trim((string) $validated['phone']);
        $linkedParent = $student?->parentUser;
        $hasUsernameColumn = Schema::hasColumn('users', 'username');

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
            'phone' => $phone,
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

        return trim(($validated['first_name'] ?? 'Student') . ' ' . ($validated['last_name'] ?? 'Parent')) . ' Parent';
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
}

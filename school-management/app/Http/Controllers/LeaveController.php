<?php

namespace App\Http\Controllers;

use App\Models\LeaveApplication;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\AcademicYear;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

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
            $linkedStudentsQuery = Student::query();
            $this->applyStudentLinkScope($linkedStudentsQuery, $user);
            $linkedStudentIds = $linkedStudentsQuery->pluck('id');

            if ($linkedStudentIds->isEmpty()) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('student_id', $linkedStudentIds);
            }
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

        if ($user->isStudent()) {
            $this->ensureStudentRecordForUser($user);
        }

        $totalStudentProfiles = Student::count();

        $students = Student::with(['schoolClass', 'section']);
        if ($user->isParent()) {
            $students->where('parent_user_id', $user->id);
        }
        if ($user->isStudent()) {
            $this->applyStudentLinkScope($students, $user);
        }

        $students = $students
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return view('leaves.create', [
            'students' => $students,
            'isSelfServiceUser' => $user->isParent() || $user->isStudent(),
            'hasAnyStudentProfiles' => $totalStudentProfiles > 0,
        ]);
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
        return view('leaves.show', [
            'leaf' => $leaf,
            'leave' => $leaf,
        ]);
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

        if ($user->isStudent() && Schema::hasColumn('students', 'student_user_id') && (int) ($student->student_user_id ?? 0) === (int) $user->id) {
            return;
        }

        if ($user->isStudent() && $this->isStudentLinkedToUser($student, $user)) {
            return;
        }

        abort(403, 'Unauthorized.');
    }

    private function isStudentLinkedToUser(Student $student, $user): bool
    {
        if (Schema::hasColumn('students', 'student_user_id') && (int) ($student->student_user_id ?? 0) === (int) $user->id) {
            return true;
        }

        $userEmail = strtolower((string) ($user->email ?? ''));
        $studentEmail = strtolower((string) ($student->email ?? ''));

        if ($userEmail !== '' && $studentEmail !== '' && $userEmail === $studentEmail) {
            return true;
        }

        $username = strtolower((string) ($user->username ?? ''));
        $admissionNo = strtolower((string) ($student->admission_no ?? ''));
        if ($username !== '' && $admissionNo !== '' && $username === $admissionNo) {
            return true;
        }

        if ($username !== '' && ctype_digit($username) && (int) $username === (int) $student->id) {
            return true;
        }

        $phone = (string) ($user->phone ?? '');
        if ($phone !== '' && in_array($phone, [
            (string) ($student->phone ?? ''),
            (string) ($student->father_phone ?? ''),
            (string) ($student->mother_phone ?? ''),
        ], true)) {
            return true;
        }

        $name = strtolower(trim((string) ($user->name ?? '')));
        if ($name !== '') {
            $fullName = strtolower(trim($student->first_name . ' ' . $student->last_name));
            if ($name === $fullName) {
                return true;
            }

            $nameParts = preg_split('/\s+/', $name);
            $firstName = strtolower((string) ($student->first_name ?? ''));
            $lastName = strtolower((string) ($student->last_name ?? ''));
            if (!empty($nameParts[0]) && $nameParts[0] === $firstName) {
                if (count($nameParts) === 1 || (!empty($nameParts[1]) && $nameParts[1] === $lastName)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function applyStudentLinkScope(Builder $query, $user): void
    {
        $userId = (int) ($user->id ?? 0);
        $email = strtolower(trim((string) ($user->email ?? '')));
        $username = strtolower(trim((string) ($user->username ?? '')));
        $phone = trim((string) ($user->phone ?? ''));
        $name = strtolower(trim((string) ($user->name ?? '')));
        $nameParts = $name !== '' ? preg_split('/\s+/', $name) : [];
        $hasAnySignal = $userId > 0 || $email !== '' || $username !== '' || $phone !== '' || $name !== '';

        if (!$hasAnySignal) {
            $query->whereRaw('1 = 0');
            return;
        }

        $query->where(function (Builder $studentQuery) use ($userId, $email, $username, $phone, $name, $nameParts) {
            if (Schema::hasColumn('students', 'student_user_id')) {
                $studentQuery->orWhere('student_user_id', $userId);
            }

            if ($email !== '') {
                $studentQuery->orWhereRaw('LOWER(email) = ?', [$email]);
            }

            if ($username !== '') {
                $studentQuery->orWhereRaw('LOWER(admission_no) = ?', [$username]);

                if (ctype_digit($username)) {
                    $studentQuery->orWhere('id', (int) $username);
                }
            }

            if ($phone !== '') {
                $studentQuery->orWhere('phone', $phone)
                    ->orWhere('father_phone', $phone)
                    ->orWhere('mother_phone', $phone);
            }

            if ($name !== '') {
                $studentQuery->orWhere(function (Builder $nameQuery) use ($name, $nameParts) {
                    $nameQuery->whereRaw('LOWER(first_name) = ?', [$name]);

                    if (!empty($nameParts[0])) {
                        if (!empty($nameParts[1])) {
                            $nameQuery->orWhere(function (Builder $firstLastQuery) use ($nameParts) {
                                $firstLastQuery->whereRaw('LOWER(first_name) = ?', [$nameParts[0]])
                                    ->whereRaw('LOWER(last_name) = ?', [$nameParts[1]]);
                            });
                        } else {
                            $nameQuery->orWhereRaw('LOWER(first_name) = ?', [$nameParts[0]]);
                        }
                    }
                });
            }
        });
    }

    private function ensureStudentRecordForUser($user): ?Student
    {
        $linkedStudentsQuery = Student::query();
        $this->applyStudentLinkScope($linkedStudentsQuery, $user);
        $existing = $linkedStudentsQuery->orderByDesc('id')->first();

        if ($existing) {
            return $existing;
        }

        $section = Section::query()->orderBy('id')->first();
        if (!$section) {
            $section = $this->ensureDefaultSection();
        }

        if (!$section) {
            return null;
        }

        $academicYear = AcademicYear::current() ?: AcademicYear::query()->orderByDesc('id')->first();
        if (!$academicYear) {
            return null;
        }

        $name = trim((string) ($user->name ?? 'Student'));
        $nameParts = preg_split('/\s+/', $name) ?: [];
        $firstName = trim((string) ($nameParts[0] ?? 'Student'));
        $lastName = trim((string) implode(' ', array_slice($nameParts, 1)));
        if ($lastName === '') {
            $lastName = 'User';
        }

        $admissionBase = strtoupper((string) ($user->username ?: $user->id));
        $admissionBase = preg_replace('/[^A-Z0-9]/', '', $admissionBase) ?: ('U' . $user->id);
        $admissionNo = 'AUTO-' . $admissionBase;
        $suffix = 1;
        while (Student::where('admission_no', $admissionNo)->exists()) {
            $admissionNo = 'AUTO-' . $admissionBase . '-' . $suffix;
            $suffix++;
        }

        $studentData = [
            'admission_no' => $admissionNo,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'gender' => 'other',
            'date_of_birth' => now()->subYears(15)->toDateString(),
            'blood_group' => null,
            'religion' => null,
            'caste' => null,
            'nationality' => 'Indian',
            'mother_tongue' => null,
            'address' => null,
            'city' => null,
            'state' => null,
            'pincode' => null,
            'phone' => $user->phone,
            'email' => $user->email,
            'photo' => null,
            'admission_date' => now()->toDateString(),
            'previous_school' => null,
            'father_name' => 'Auto Linked',
            'father_phone' => $user->phone,
            'father_occupation' => null,
            'mother_name' => null,
            'mother_phone' => null,
            'mother_occupation' => null,
            'guardian_name' => null,
            'guardian_phone' => null,
            'guardian_relation' => null,
            'class_id' => $section->class_id,
            'section_id' => $section->id,
            'academic_year_id' => $academicYear->id,
            'parent_user_id' => null,
            'status' => 'active',
        ];

        if (Schema::hasColumn('students', 'student_user_id')) {
            $studentData['student_user_id'] = $user->id;
        }

        return Student::create($studentData);
    }

    private function ensureDefaultSection(): ?Section
    {
        $section = Section::query()->orderBy('id')->first();
        if ($section) {
            return $section;
        }

        $class = SchoolClass::query()->orderBy('id')->first();
        if (!$class) {
            $class = SchoolClass::create([
                'name' => 'Class 1',
                'numeric_name' => '1',
            ]);
        }

        return Section::create([
            'class_id' => $class->id,
            'name' => 'A',
        ]);
    }
}

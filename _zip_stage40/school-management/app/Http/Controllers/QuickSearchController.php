<?php

namespace App\Http\Controllers;

use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\Student;
use App\Support\FeeStructureApplicability;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class QuickSearchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($this->canUseQuickSearch($user), 403, 'Unauthorized.');

        $startedAt = microtime(true);
        $rawQuery = trim((string) $request->query('q', ''));

        if (mb_strlen($rawQuery) < 2) {
            return response()->json([
                'query' => $rawQuery,
                'groups' => [
                    'students' => [],
                    'parents' => [],
                    'fee_records' => [],
                    'staff' => [],
                ],
                'total' => 0,
                'took_ms' => 0,
            ]);
        }

        $query = mb_strtolower($rawQuery);
        $intent = $this->parseIntent($query);

        $groups = Cache::remember(
            'quick-search:' . $user->id . ':' . md5($rawQuery),
            now()->addSeconds(30),
            function () use ($user, $rawQuery, $intent) {
                $students = $this->searchStudents($user, $rawQuery, $intent);
                $parents = $this->searchParents($user, $rawQuery, $intent);
                $feeRecords = $this->searchFeeRecords($user, $rawQuery, $intent);
                $staff = $this->searchStaff($user, $rawQuery);

                return [
                    'students' => $students->values()->all(),
                    'parents' => $parents->values()->all(),
                    'fee_records' => $feeRecords->values()->all(),
                    'staff' => $staff->values()->all(),
                ];
            }
        );

        $total = collect($groups)->sum(fn ($items) => count($items));

        return response()->json([
            'query' => $rawQuery,
            'groups' => $groups,
            'total' => $total,
            'took_ms' => (int) round((microtime(true) - $startedAt) * 1000),
        ]);
    }

    private function canUseQuickSearch(User $user): bool
    {
        if ($user->isParent() || $user->isStudent()) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'teacher', 'cashier'])
            || $user->hasPermission('students.manage')
            || $user->hasPermission('fees.payments.manage')
            || $user->hasPermission('users.manage');
    }

    private function parseIntent(string $query): array
    {
        $intent = [
            'fees_due' => str_contains($query, 'fees due') || str_contains($query, 'due fees') || str_contains($query, 'pending fees'),
            'paid_today' => str_contains($query, 'paid today') || str_contains($query, 'today paid') || str_contains($query, 'payments today'),
            'occupation_term' => null,
            'class' => null,
            'section' => null,
            'date' => null,
        ];

        if (preg_match('/\b(?:father|mother)\s+([a-z0-9\-\s]{2,30})\b/i', $query, $matches)) {
            $intent['occupation_term'] = trim($matches[1]);
        } elseif (preg_match('/\b(business|doctor|engineer|teacher|farmer|driver|accountant|lawyer)\b/i', $query, $matches)) {
            $intent['occupation_term'] = trim($matches[1]);
        }

        if (preg_match('/\bclass\s+([a-z0-9\-]+)(?:\s+([a-z]))?\b/i', $query, $matches)) {
            $intent['class'] = trim($matches[1]);
            $intent['section'] = isset($matches[2]) ? trim($matches[2]) : null;
        }

        if (preg_match('/\b(\d{4}-\d{2}-\d{2})\b/', $query, $matches)) {
            $intent['date'] = $matches[1];
        }

        return $intent;
    }

    private function searchStudents($user, string $search, array $intent): Collection
    {
        if (!$user->hasPermission('students.manage') && !$user->isParent() && !$user->isStudent()) {
            return collect();
        }

        $isClassIntentQuery = !empty($intent['class']) && preg_match('/\bclass\s+/i', $search);
        $isOccupationIntentPhrase = preg_match('/\b(?:father|mother)\s+[a-z0-9\-\s]{2,30}\b/i', $search) === 1;

        $query = Student::query()
            ->select([
                'students.id',
                'students.admission_no',
                'students.first_name',
                'students.last_name',
                'students.father_name',
                'students.mother_name',
                'students.phone',
                'students.father_phone',
                'students.address',
                'students.class_id',
                'students.section_id',
                'students.parent_user_id',
            ])
            ->leftJoin('student_profiles', 'student_profiles.student_id', '=', 'students.id')
            ->leftJoin('classes', 'classes.id', '=', 'students.class_id')
            ->leftJoin('sections', 'sections.id', '=', 'students.section_id')
            ;

        if (!$isClassIntentQuery && !$isOccupationIntentPhrase) {
            $query->where(function ($studentQuery) use ($search) {
                $studentQuery->where('students.first_name', 'like', "%{$search}%")
                    ->orWhere('students.last_name', 'like', "%{$search}%")
                    ->orWhereRaw("CONCAT(students.first_name, ' ', students.last_name) LIKE ?", ["%{$search}%"])
                    ->orWhere('students.admission_no', 'like', "%{$search}%")
                    ->orWhere('students.father_name', 'like', "%{$search}%")
                    ->orWhere('students.mother_name', 'like', "%{$search}%")
                    ->orWhere('students.phone', 'like', "%{$search}%")
                    ->orWhere('students.father_phone', 'like', "%{$search}%")
                    ->orWhere('students.address', 'like', "%{$search}%")
                    ->orWhere('student_profiles.father_occupation', 'like', "%{$search}%")
                    ->orWhere('student_profiles.mother_occupation', 'like', "%{$search}%");
            });
        }

        if (!$user->hasPermission('students.manage')) {
            if ($user->isParent()) {
                $query->where('students.parent_user_id', $user->id);
            } elseif ($user->isStudent()) {
                $query->where('students.email', $user->email);
            }
        }

        if (!empty($intent['occupation_term'])) {
            $occupationTerm = $intent['occupation_term'];
            $query->where(function ($occupationQuery) use ($occupationTerm) {
                $occupationQuery->where('students.father_occupation', 'like', "%{$occupationTerm}%")
                    ->orWhere('students.mother_occupation', 'like', "%{$occupationTerm}%")
                    ->orWhere('student_profiles.father_occupation', 'like', "%{$occupationTerm}%")
                    ->orWhere('student_profiles.mother_occupation', 'like', "%{$occupationTerm}%");
            });
        }

        if (!empty($intent['class'])) {
            $query->where('classes.name', 'like', "%{$intent['class']}%");
        }

        if (!empty($intent['section'])) {
            $query->where('sections.name', 'like', "%{$intent['section']}%");
        }

        return $query
            ->with(['schoolClass:id,name', 'section:id,name'])
            ->orderBy('students.first_name')
            ->limit(8)
            ->get()
            ->map(function (Student $student) use ($user) {
                return [
                    'id' => $student->id,
                    'title' => $student->full_name,
                    'subtitle' => trim(($student->admission_no ? $student->admission_no . ' | ' : '') . ($student->schoolClass?->name ?? '-') . (($student->section?->name ?? null) ? ' - ' . $student->section->name : '')),
                    'meta' => trim(($student->father_name ? 'Father: ' . $student->father_name . ' | ' : '') . ($student->phone ?: $student->father_phone ?: '-')),
                    'url' => $this->studentUrl($user, $student),
                ];
            });
    }

    private function searchParents($user, string $search, array $intent): Collection
    {
        if (!$user->hasPermission('students.manage') && !$user->hasPermission('users.manage') && !$user->isParent() && !$user->isStudent()) {
            return collect();
        }

        $isOccupationIntentPhrase = preg_match('/\b(?:father|mother)\s+[a-z0-9\-\s]{2,30}\b/i', $search) === 1;

        $query = Student::query()
            ->select([
                'students.id',
                'students.first_name',
                'students.last_name',
                'students.father_name',
                'students.mother_name',
                'students.father_phone',
                'students.mother_phone',
                'students.father_occupation',
                'students.mother_occupation',
                'students.parent_user_id',
            ])
            ->leftJoin('student_profiles', 'student_profiles.student_id', '=', 'students.id')
            ;

        if (!$isOccupationIntentPhrase) {
            $query->where(function ($parentQuery) use ($search) {
                $parentQuery->where('students.father_name', 'like', "%{$search}%")
                    ->orWhere('students.mother_name', 'like', "%{$search}%")
                    ->orWhere('students.father_phone', 'like', "%{$search}%")
                    ->orWhere('students.mother_phone', 'like', "%{$search}%")
                    ->orWhere('students.father_occupation', 'like', "%{$search}%")
                    ->orWhere('students.mother_occupation', 'like', "%{$search}%")
                    ->orWhere('student_profiles.father_occupation', 'like', "%{$search}%")
                    ->orWhere('student_profiles.mother_occupation', 'like', "%{$search}%");
            });
        }

        if (!$user->hasPermission('students.manage')) {
            if ($user->isParent()) {
                $query->where('students.parent_user_id', $user->id);
            } elseif ($user->isStudent()) {
                $query->where('students.email', $user->email);
            }
        }

        if (!empty($intent['occupation_term'])) {
            $occupationTerm = $intent['occupation_term'];
            $query->where(function ($occupationQuery) use ($occupationTerm) {
                $occupationQuery->where('students.father_occupation', 'like', "%{$occupationTerm}%")
                    ->orWhere('students.mother_occupation', 'like', "%{$occupationTerm}%")
                    ->orWhere('student_profiles.father_occupation', 'like', "%{$occupationTerm}%")
                    ->orWhere('student_profiles.mother_occupation', 'like', "%{$occupationTerm}%");
            });
        }

        return $query
            ->orderBy('students.first_name')
            ->limit(8)
            ->get()
            ->map(function (Student $student) use ($user) {
                $occupation = $student->father_occupation ?: $student->mother_occupation ?: '-';

                return [
                    'id' => $student->id,
                    'title' => trim(($student->father_name ?: 'N/A') . ' / ' . ($student->mother_name ?: 'N/A')),
                    'subtitle' => 'Student: ' . $student->full_name,
                    'meta' => trim('Phone: ' . ($student->father_phone ?: $student->mother_phone ?: '-') . ' | Occupation: ' . $occupation),
                    'url' => $this->studentUrl($user, $student),
                ];
            });
    }

    private function searchFeeRecords($user, string $search, array $intent): Collection
    {
        if (!$user->hasPermission('fees.payments.manage') && !$user->isParent() && !$user->isStudent()) {
            return collect();
        }

        if ($intent['fees_due']) {
            return $this->searchDueFees($user, $search, $intent);
        }

        $isIntentOnlyPaidTodayQuery = $intent['paid_today']
            && preg_match('/\b(paid\s+today|today\s+paid|payments\s+today)\b/i', $search);

        $query = FeePayment::query()
            ->select([
                'fee_payments.id',
                'fee_payments.student_id',
                'fee_payments.receipt_no',
                'fee_payments.amount_paid',
                'fee_payments.payment_date',
                'fee_payments.status',
            ])
            ->with(['student:id,first_name,last_name,admission_no,parent_user_id,email', 'feeStructure.feeCategory:id,name'])
            ->leftJoin('students', 'students.id', '=', 'fee_payments.student_id')
            ->distinct('fee_payments.id');

        if (!$isIntentOnlyPaidTodayQuery) {
            $query->where(function ($paymentQuery) use ($search, $intent) {
                $paymentQuery->where('fee_payments.receipt_no', 'like', "%{$search}%")
                    ->orWhere('students.first_name', 'like', "%{$search}%")
                    ->orWhere('students.last_name', 'like', "%{$search}%")
                    ->orWhereRaw("CONCAT(students.first_name, ' ', students.last_name) LIKE ?", ["%{$search}%"])
                    ->orWhere('students.admission_no', 'like', "%{$search}%");

                if (!empty($intent['date'])) {
                    $paymentQuery->orWhereDate('fee_payments.payment_date', $intent['date']);
                }
            });
        }

        if (!$user->hasPermission('fees.payments.manage')) {
            if ($user->isParent()) {
                $query->where('students.parent_user_id', $user->id);
            } elseif ($user->isStudent()) {
                $query->where('students.email', $user->email);
            }
        }

        if ($intent['paid_today']) {
            $query->whereDate('fee_payments.payment_date', today());
        }

        return $query
            ->orderByDesc('fee_payments.payment_date')
            ->limit(8)
            ->get(['fee_payments.*'])
            ->map(function (FeePayment $payment) use ($user) {
                return [
                    'id' => $payment->id,
                    'title' => $payment->receipt_no,
                    'subtitle' => ($payment->student?->full_name ?? 'Unknown Student') . ' | ' . ($payment->feeStructure?->display_name ?? 'Fee'),
                    'meta' => 'Rs ' . number_format((float) $payment->amount_paid, 2) . ' | ' . optional($payment->payment_date)->format('d M Y') . ' | ' . ucfirst($payment->status),
                    'url' => $this->feeRecordUrl($user, $payment),
                ];
            });
    }

    private function searchDueFees($user, string $search, array $intent): Collection
    {
        $isIntentOnlyDueQuery = preg_match('/\b(fees\s+due|due\s+fees|pending\s+fees)\b/i', $search) === 1;

        $studentQuery = Student::query()
            ->select([
                'students.id',
                'students.first_name',
                'students.last_name',
                'students.admission_no',
                'students.class_id',
                'students.academic_year_id',
                'students.parent_user_id',
                'students.email',
                'students.admission_date',
            ])
            ->leftJoin('classes', 'classes.id', '=', 'students.class_id')
            ->leftJoin('sections', 'sections.id', '=', 'students.section_id')
            ->where('students.status', 'active');

        if (!$isIntentOnlyDueQuery) {
            $studentQuery->where(function ($query) use ($search) {
                $query->where('students.first_name', 'like', "%{$search}%")
                    ->orWhere('students.last_name', 'like', "%{$search}%")
                    ->orWhereRaw("CONCAT(students.first_name, ' ', students.last_name) LIKE ?", ["%{$search}%"])
                    ->orWhere('students.admission_no', 'like', "%{$search}%")
                    ->orWhere('classes.name', 'like', "%{$search}%")
                    ->orWhere('sections.name', 'like', "%{$search}%");
            });
        }

        if (!$user->hasPermission('fees.payments.manage')) {
            if ($user->isParent()) {
                $studentQuery->where('students.parent_user_id', $user->id);
            } elseif ($user->isStudent()) {
                $studentQuery->where('students.email', $user->email);
            }
        }

        if (!empty($intent['class'])) {
            $studentQuery->where('classes.name', 'like', "%{$intent['class']}%");
        }

        if (!empty($intent['section'])) {
            $studentQuery->where('sections.name', 'like', "%{$intent['section']}%");
        }

        $students = $studentQuery->limit(20)->get();
        if ($students->isEmpty()) {
            return collect();
        }

        $students->load('academicYear');

        $classYearKeys = $students->map(fn ($s) => $s->class_id . '-' . $s->academic_year_id)->unique();
        $validClassYearPairs = $classYearKeys
            ->map(function ($key) {
                [$classId, $academicYearId] = explode('-', $key);
                if (empty($classId) || empty($academicYearId)) {
                    return null;
                }

                return ['class_id' => (int) $classId, 'academic_year_id' => (int) $academicYearId];
            })
            ->filter()
            ->values();

        if ($validClassYearPairs->isEmpty()) {
            return collect();
        }

        $structures = FeeStructure::query()
            ->where(function ($query) use ($validClassYearPairs) {
                foreach ($validClassYearPairs as $pair) {
                    $query->orWhere(function ($pairQuery) use ($pair) {
                        $pairQuery->where('class_id', $pair['class_id'])->where('academic_year_id', $pair['academic_year_id']);
                    });
                }
            })
            ->get()
            ->groupBy(fn ($item) => $item->class_id . '-' . $item->academic_year_id);

        $paymentMap = FeePayment::query()
            ->select('student_id', 'fee_structure_id', DB::raw('SUM(amount_paid + discount) as total_settled'))
            ->whereIn('student_id', $students->pluck('id'))
            ->groupBy('student_id', 'fee_structure_id')
            ->get()
            ->mapWithKeys(function ($payment) {
                return [$payment->student_id . '-' . $payment->fee_structure_id => (float) $payment->total_settled];
            });

        return $students->map(function ($student) use ($structures, $paymentMap, $user) {
            $totalDue = 0.0;
            $items = $structures->get($student->class_id . '-' . $student->academic_year_id, collect());

            foreach ($items as $structure) {
                if (!FeeStructureApplicability::appliesToStudent($structure, $student)) {
                    continue;
                }

                $assigned = (float) $structure->amount;
                $paid = (float) ($paymentMap[$student->id . '-' . $structure->id] ?? 0);
                $totalDue += max(0, $assigned - $paid);
            }

            return [
                'id' => $student->id,
                'title' => $student->first_name . ' ' . $student->last_name,
                'subtitle' => $student->admission_no,
                'meta' => 'Due: Rs ' . number_format($totalDue, 2),
                'url' => $user->hasPermission('fees.payments.manage') ? route('fees.due') : route('fees.my-fees'),
                'due' => $totalDue,
            ];
        })
            ->filter(fn ($item) => $item['due'] > 0)
            ->sortByDesc('due')
            ->take(8)
            ->map(function ($item) {
                unset($item['due']);
                return $item;
            })
            ->values();
    }

    private function searchStaff($user, string $search): Collection
    {
        if (!$user->hasPermission('users.manage')) {
            return collect();
        }

        $query = User::query()
            ->whereIn('role', ['admin', 'teacher', 'cashier'])
            ->where(function ($staffQuery) use ($search) {
                $staffQuery->where('name', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");

                if (Schema::hasColumn('users', 'department')) {
                    $staffQuery->orWhere('department', 'like', "%{$search}%");
                }
            })
            ->orderBy('name')
            ->limit(8)
            ->get();

        return $query->map(function (User $staff) {
            $department = Schema::hasColumn('users', 'department')
                ? ($staff->department ?: ucfirst($staff->role))
                : ucfirst($staff->role);

            return [
                'id' => $staff->id,
                'title' => $staff->name,
                'subtitle' => ucfirst($staff->role) . ' | ' . $department,
                'meta' => $staff->phone ?: ($staff->email ?: '-'),
                'url' => route('settings.users', ['search' => $staff->email ?: $staff->name]),
            ];
        });
    }

    private function studentUrl($user, Student $student): string
    {
        if ($user->hasPermission('students.manage')) {
            return route('students.show', $student);
        }

        if ($user->isParent() || $user->isStudent()) {
            return route('fees.my-fees');
        }

        return route('dashboard');
    }

    private function feeRecordUrl($user, FeePayment $payment): string
    {
        if ($user->hasPermission('fees.payments.manage')) {
            return route('fees.payments.show', $payment);
        }

        return route('fees.my-fees');
    }
}

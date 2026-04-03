<?php

namespace App\Http\Controllers;

use App\Models\FeeCategory;
use App\Models\FeeDiscountRecord;
use App\Models\FeeStructure;
use App\Models\FeePayment;
use App\Models\NotificationSetting;
use App\Models\PaymentGatewaySetting;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Throwable;

class FeeController extends Controller
{
    // Fee Categories
    public function categories()
    {
        $categories = FeeCategory::withCount('feeStructures')->get();
        return view('fees.categories', compact('categories'));
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        FeeCategory::create($validated);
        return redirect()->route('fees.categories')->with('success', 'Fee category created.');
    }

    public function destroyCategory(FeeCategory $category)
    {
        $category->delete();
        return redirect()->route('fees.categories')->with('success', 'Fee category deleted.');
    }

    // Fee Structure
    public function structures(Request $request)
    {
        $query = FeeStructure::with(['feeCategory', 'schoolClass', 'academicYear']);

        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }

        $structures = $query->latest()->paginate(20);
        $categories = FeeCategory::all();
        $classes = SchoolClass::all();
        $academicYears = AcademicYear::all();

        return view('fees.structures', compact('structures', 'categories', 'classes', 'academicYears'));
    }

    public function storeStructure(Request $request)
    {
        $validated = $request->validate([
            'fee_category_id' => 'required|exists:fee_categories,id',
            'class_id' => 'required|exists:classes,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'amount' => 'required|numeric|min:0',
            'frequency' => 'required|in:monthly,quarterly,half_yearly,yearly,one_time',
            'due_date' => 'nullable|date',
        ]);

        FeeStructure::create($validated);
        return redirect()->route('fees.structures')->with('success', 'Fee structure created.');
    }

    public function destroyStructure(FeeStructure $structure)
    {
        $structure->delete();
        return redirect()->route('fees.structures')->with('success', 'Fee structure deleted.');
    }

    // Fee Payments
    public function payments(Request $request)
    {
        $query = FeePayment::with(['student', 'feeStructure.feeCategory', 'collector']);

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }
        if ($request->filled('search')) {
            $search = trim($request->search);
            $fullNameExpression = $this->studentFullNameExpression();
            $query->whereHas('student', function ($studentQuery) use ($search, $fullNameExpression) {
                $studentQuery->where(function ($q) use ($search, $fullNameExpression) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhereRaw($fullNameExpression . " LIKE ?", ["%{$search}%"])
                        ->orWhere('admission_no', 'like', "%{$search}%")
                        ->orWhere('father_name', 'like', "%{$search}%")
                        ->orWhere('mother_name', 'like', "%{$search}%")
                        ->orWhere('guardian_name', 'like', "%{$search}%");
                });
            });
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('payment_location')) {
            $query->where('payment_location', $request->payment_location);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('payment_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('payment_date', '<=', $request->date_to);
        }

        $payments = $query->latest()->paginate(20);
        return view('fees.payments', compact('payments'));
    }

    public function discounts(Request $request)
    {
        $query = FeeDiscountRecord::query()
            ->with([
                'student:id,first_name,last_name,admission_no,class_id,section_id',
                'student.schoolClass:id,name',
                'student.section:id,name',
                'feeStructure:id,fee_category_id,amount',
                'feeStructure.feeCategory:id,name',
                'payment:id,receipt_no,payment_date,status',
                'createdBy:id,name',
            ]);

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $fullNameExpression = $this->studentFullNameExpression();

            $query->whereHas('student', function ($studentQuery) use ($search, $fullNameExpression) {
                $studentQuery->where(function ($q) use ($search, $fullNameExpression) {
                    $q->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhereRaw($fullNameExpression . " LIKE ?", ["%{$search}%"])
                        ->orWhere('admission_no', 'like', "%{$search}%")
                        ->orWhere('father_name', 'like', "%{$search}%");
                });
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $discounts = $query->latest()->paginate(20)->withQueryString();

        $summary = [
            'total_discount_amount' => (clone $query)->sum('discount_amount'),
            'total_discount_records' => (clone $query)->count(),
        ];

        return view('fees.discounts', compact('discounts', 'summary'));
    }

    public function dueFees(Request $request)
    {
        $studentsQuery = Student::with(['schoolClass:id,name', 'section:id,name'])
            ->where('status', 'active');

        if ($request->filled('class_id')) {
            $studentsQuery->where('class_id', $request->class_id);
        }

        if ($request->filled('section_id')) {
            $studentsQuery->where('section_id', $request->section_id);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $fullNameExpression = $this->studentFullNameExpression();

            $studentsQuery->where(function ($query) use ($search, $fullNameExpression) {
                $query->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhereRaw($fullNameExpression . " LIKE ?", ["%{$search}%"])
                    ->orWhere('admission_no', 'like', "%{$search}%");
            });
        }

        $students = $studentsQuery
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $dueRows = $this->buildDueRowsForStudents($students);
        $perPage = 20;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $pageItems = $dueRows->forPage($currentPage, $perPage)->values();
        $dueStudents = new LengthAwarePaginator(
            $pageItems,
            $dueRows->count(),
            $perPage,
            $currentPage,
            ['path' => url()->current(), 'query' => $request->query()]
        );

        $classes = SchoolClass::orderBy('name')->get();

        return view('fees.due-fees', [
            'dueStudents' => $dueStudents,
            'classes' => $classes,
            'totalDueAmount' => $dueRows->sum('total_due'),
            'totalDueHeads' => $dueRows->sum('due_heads'),
        ]);
    }

    public function createPayment(Request $request)
    {
        $students = Student::with(['schoolClass:id,name', 'section:id,name', 'parentUser:id,name'])
            ->where('status', 'active')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
        $gatewaySettings = PaymentGatewaySetting::firstOrCreate(
            ['provider' => 'razorpay'],
            ['is_enabled' => false, 'currency' => 'INR']
        );
        $selectedStudent = null;
        $selectedStructures = collect();

        if ($request->filled('student_id')) {
            $selectedStudent = $students->firstWhere('id', (int) $request->student_id);
            if ($selectedStudent) {
                $selectedStructures = $this->feeStructuresForStudent($selectedStudent);
            }
        }

        return view('fees.create-payment', compact('students', 'gatewaySettings', 'selectedStudent', 'selectedStructures'));
    }

    public function storePayment(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'fee_structure_id' => 'required|exists:fee_structures,id',
            'amount_paid' => 'required|numeric|min:0',
            'discount' => 'nullable|numeric|min:0',
            'fine' => 'nullable|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_location' => 'required|in:school,bank',
            'payment_channel' => 'required|in:cash,upi,cheque,bank_transfer',
            'transaction_id' => 'nullable|string|max:255',
            'utr_number' => 'nullable|string|max:255',
            'cheque_number' => 'nullable|string|max:255',
            'razorpay_order_id' => 'nullable|string|max:255',
            'razorpay_payment_id' => 'nullable|string|max:255',
            'razorpay_signature' => 'nullable|string|max:255',
            'status' => 'required|in:paid,partial',
            'remarks' => 'nullable|string',
        ]);

        $validated['payment_method'] = $this->mapLegacyPaymentMethod($validated['payment_channel']);

        if ($validated['payment_channel'] === 'upi') {
            $hasRazorpayPayload = !empty($validated['razorpay_order_id'])
                && !empty($validated['razorpay_payment_id'])
                && !empty($validated['razorpay_signature']);

            if ($hasRazorpayPayload) {
                $gatewaySettings = PaymentGatewaySetting::query()
                    ->where('provider', 'razorpay')
                    ->first();

                if (!$gatewaySettings || !$gatewaySettings->is_enabled || blank($gatewaySettings->key_secret)) {
                    return back()->withErrors([
                        'payment_method' => 'Razorpay is not configured for online payments.',
                    ])->withInput();
                }

                if (!$this->isValidRazorpaySignature(
                    $validated['razorpay_order_id'],
                    $validated['razorpay_payment_id'],
                    $validated['razorpay_signature'],
                    $gatewaySettings->key_secret
                )) {
                    return back()->withErrors([
                        'payment_method' => 'Razorpay signature verification failed. Please retry.',
                    ])->withInput();
                }

                $validated['transaction_id'] = $validated['razorpay_payment_id'];
                $validated['status'] = 'paid';
            }

            if (empty($validated['transaction_id'])) {
                return back()->withErrors([
                    'transaction_id' => 'Transaction ID is required for online payments.',
                ])->withInput();
            }
        }

        unset(
            $validated['razorpay_order_id'],
            $validated['razorpay_payment_id'],
            $validated['razorpay_signature']
        );

        $validated['receipt_no'] = 'RCP-' . date('Ymd') . '-' . str_pad(FeePayment::count() + 1, 4, '0', STR_PAD_LEFT);
        $validated['collected_by'] = auth()->id();
        $validated['discount'] = $validated['discount'] ?? 0;
        $validated['fine'] = $validated['fine'] ?? 0;

        $payment = FeePayment::create($validated);
        $this->recordDiscountIfNeeded($payment, $validated['discount'], $validated['remarks'] ?? null);

        return redirect()->route('fees.payments')->with('success', 'Payment recorded successfully.');
    }

    public function showPayment(FeePayment $payment)
    {
        $payment->load(['student', 'feeStructure.feeCategory', 'collector']);
        return view('fees.show-payment', compact('payment'));
    }

    public function createRazorpayOrder(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
        ]);

        $gatewaySettings = PaymentGatewaySetting::query()
            ->where('provider', 'razorpay')
            ->first();

        if (
            !$gatewaySettings
            || !$gatewaySettings->is_enabled
            || blank($gatewaySettings->key_id)
            || blank($gatewaySettings->key_secret)
        ) {
            return response()->json([
                'message' => 'Razorpay is not configured. Please update payment settings first.',
            ], 422);
        }

        $amountInPaise = (int) round(((float) $validated['amount']) * 100);
        $currency = strtoupper($gatewaySettings->currency ?: 'INR');

        try {
            $response = Http::withBasicAuth($gatewaySettings->key_id, $gatewaySettings->key_secret)
                ->acceptJson()
                ->post('https://api.razorpay.com/v1/orders', [
                    'amount' => $amountInPaise,
                    'currency' => $currency,
                    'receipt' => 'fee_' . now()->format('YmdHis') . '_' . random_int(1000, 9999),
                    'payment_capture' => 1,
                ]);
        } catch (Throwable $exception) {
            return response()->json([
                'message' => 'Unable to connect to Razorpay. Please try again.',
            ], 422);
        }

        if ($response->failed()) {
            $errorMessage = $response->json('error.description')
                ?? $response->json('message')
                ?? 'Unable to create Razorpay order.';

            return response()->json([
                'message' => $errorMessage,
            ], 422);
        }

        $order = $response->json();

        return response()->json([
            'order_id' => $order['id'] ?? null,
            'amount' => $order['amount'] ?? $amountInPaise,
            'currency' => $order['currency'] ?? $currency,
            'key_id' => $gatewaySettings->key_id,
            'name' => config('app.name', 'School Management System'),
            'description' => 'Fee Payment',
        ]);
    }

    public function createMyRazorpayOrder(Request $request)
    {
        $user = auth()->user();
        abort_unless($user->isParent() || $user->isStudent(), 403, 'Unauthorized.');

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'fee_structure_id' => 'required|exists:fee_structures,id',
            'amount' => 'required|numeric|min:1',
        ]);

        $student = Student::findOrFail($validated['student_id']);
        abort_unless($this->userCanAccessStudent($user, $student), 403, 'Unauthorized.');

        $structure = FeeStructure::with('feeCategory')
            ->where('id', $validated['fee_structure_id'])
            ->where('class_id', $student->class_id)
            ->where('academic_year_id', $student->academic_year_id)
            ->firstOrFail();

        $paidAmount = (float) FeePayment::query()
            ->where('student_id', $student->id)
            ->where('fee_structure_id', $structure->id)
            ->sum('amount_paid');

        $dueAmount = max(0, (float) $structure->amount - $paidAmount);
        abort_if($dueAmount <= 0, 422, 'This fee is already fully paid.');
        abort_if((float) $validated['amount'] > $dueAmount, 422, 'Entered amount exceeds the due fee amount.');

        return $this->createRazorpayOrder(new Request([
            'amount' => $validated['amount'],
        ]));
    }

    public function verifyRazorpayPayment(Request $request)
    {
        $validated = $request->validate([
            'razorpay_order_id' => 'required|string|max:255',
            'razorpay_payment_id' => 'required|string|max:255',
            'razorpay_signature' => 'required|string|max:255',
        ]);

        $gatewaySettings = PaymentGatewaySetting::query()
            ->where('provider', 'razorpay')
            ->first();

        if (!$gatewaySettings || blank($gatewaySettings->key_secret)) {
            return response()->json([
                'verified' => false,
                'message' => 'Razorpay credentials are missing.',
            ], 422);
        }

        $isValid = $this->isValidRazorpaySignature(
            $validated['razorpay_order_id'],
            $validated['razorpay_payment_id'],
            $validated['razorpay_signature'],
            $gatewaySettings->key_secret
        );

        if (!$isValid) {
            return response()->json([
                'verified' => false,
                'message' => 'Invalid payment signature.',
            ], 422);
        }

        return response()->json([
            'verified' => true,
        ]);
    }

    public function getStudentFees(Student $student)
    {
        $structures = $this->feeStructuresForStudent($student);

        return response()->json($structures);
    }

    public function myFees()
    {
        $user = auth()->user();
        abort_unless($user->isParent() || $user->isStudent(), 403, 'Unauthorized.');

        $studentsQuery = Student::with(['schoolClass:id,name', 'section:id,name']);

        if ($user->isParent()) {
            $studentsQuery->where('parent_user_id', $user->id);
        } else {
            $studentsQuery->where('email', $user->email);
        }

        $students = $studentsQuery
            ->where('status', 'active')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $studentIds = $students->pluck('id');
        $recentPayments = FeePayment::with(['student', 'feeStructure.feeCategory'])
            ->whereIn('student_id', $studentIds)
            ->latest()
            ->take(12)
            ->get();

        $feeOverview = $this->buildFeeOverviewForStudents($students);

        return view('fees.my-fees', [
            'students' => $students,
            'recentPayments' => $recentPayments,
            'feeOverview' => $feeOverview,
            'gatewaySettings' => PaymentGatewaySetting::first(),
        ]);
    }

    public function storeMyOnlinePayment(Request $request)
    {
        $user = auth()->user();
        abort_unless($user->isParent() || $user->isStudent(), 403, 'Unauthorized.');

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'fee_structure_id' => 'required|exists:fee_structures,id',
            'amount_paid' => 'required|numeric|min:1',
            'payment_date' => 'required|date',
            'razorpay_order_id' => 'required|string|max:255',
            'razorpay_payment_id' => 'required|string|max:255',
            'razorpay_signature' => 'required|string|max:255',
        ]);

        $student = Student::findOrFail($validated['student_id']);
        abort_unless($this->userCanAccessStudent($user, $student), 403, 'Unauthorized.');

        $structure = FeeStructure::query()
            ->where('id', $validated['fee_structure_id'])
            ->where('class_id', $student->class_id)
            ->where('academic_year_id', $student->academic_year_id)
            ->firstOrFail();

        $gatewaySettings = PaymentGatewaySetting::query()
            ->where('provider', 'razorpay')
            ->first();

        if (!$gatewaySettings || !$gatewaySettings->is_enabled || blank($gatewaySettings->key_secret)) {
            return back()->withErrors([
                'payment' => 'Razorpay is not configured for online payments.',
            ]);
        }

        if (!$this->isValidRazorpaySignature(
            $validated['razorpay_order_id'],
            $validated['razorpay_payment_id'],
            $validated['razorpay_signature'],
            $gatewaySettings->key_secret
        )) {
            return back()->withErrors([
                'payment' => 'Payment verification failed. Please retry.',
            ]);
        }

        $paidAmount = (float) FeePayment::query()
            ->where('student_id', $student->id)
            ->where('fee_structure_id', $structure->id)
            ->sum('amount_paid');
        $dueAmount = max(0, (float) $structure->amount - $paidAmount);

        if ($dueAmount <= 0) {
            return back()->withErrors([
                'payment' => 'This fee has already been fully paid.',
            ]);
        }

        if ((float) $validated['amount_paid'] > $dueAmount) {
            return back()->withErrors([
                'payment' => 'Entered amount exceeds the due fee amount.',
            ]);
        }

        FeePayment::create([
            'student_id' => $student->id,
            'fee_structure_id' => $structure->id,
            'amount_paid' => $validated['amount_paid'],
            'discount' => 0,
            'fine' => 0,
            'payment_date' => $validated['payment_date'],
            'payment_method' => 'online',
            'payment_location' => 'bank',
            'payment_channel' => 'upi',
            'transaction_id' => $validated['razorpay_payment_id'],
            'receipt_no' => 'RCP-' . date('Ymd') . '-' . str_pad(FeePayment::count() + 1, 4, '0', STR_PAD_LEFT),
            'status' => (float) $validated['amount_paid'] >= $dueAmount ? 'paid' : 'partial',
            'remarks' => 'Paid from parent/student portal via Razorpay',
            'collected_by' => $user->id,
        ]);

        return redirect()->route('fees.my-fees')->with('success', 'Online payment recorded successfully.');
    }

    private function isValidRazorpaySignature(string $orderId, string $paymentId, string $signature, string $secret): bool
    {
        $payload = $orderId . '|' . $paymentId;
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    private function feeStructuresForStudent(Student $student): Collection
    {
        return FeeStructure::with(['feeCategory', 'schoolClass'])
            ->where('class_id', $student->class_id)
            ->where('academic_year_id', $student->academic_year_id)
            ->orderBy('fee_category_id')
            ->get();
    }

    private function buildFeeOverviewForStudents(Collection $students): array
    {
        $overviewItems = [];
        $totalDueAmount = 0;
        $totalPaidAmount = 0;

        foreach ($students as $student) {
            $structures = $this->feeStructuresForStudent($student);

            foreach ($structures as $structure) {
                $paidAmount = (float) FeePayment::query()
                    ->where('student_id', $student->id)
                    ->where('fee_structure_id', $structure->id)
                    ->sum('amount_paid');

                $dueAmount = max(0, (float) $structure->amount - $paidAmount);
                $totalPaidAmount += $paidAmount;
                $totalDueAmount += $dueAmount;

                $overviewItems[] = [
                    'student' => $student,
                    'structure' => $structure,
                    'paid_amount' => $paidAmount,
                    'due_amount' => $dueAmount,
                ];
            }
        }

        return [
            'items' => collect($overviewItems)->sortByDesc('due_amount')->values(),
            'due_amount' => $totalDueAmount,
            'paid_amount' => $totalPaidAmount,
        ];
    }

    private function buildDueRowsForStudents(Collection $students): Collection
    {
        if ($students->isEmpty()) {
            return collect();
        }

        $classIds = $students->pluck('class_id')->filter()->unique()->values();
        $academicYearIds = $students->pluck('academic_year_id')->filter()->unique()->values();

        $structures = FeeStructure::with('feeCategory:id,name')
            ->whereIn('class_id', $classIds)
            ->whereIn('academic_year_id', $academicYearIds)
            ->get()
            ->groupBy(fn ($structure) => $structure->class_id . '-' . $structure->academic_year_id);

        $paymentMap = FeePayment::query()
            ->select('student_id', 'fee_structure_id', DB::raw('SUM(amount_paid) as total_paid'))
            ->whereIn('student_id', $students->pluck('id'))
            ->groupBy('student_id', 'fee_structure_id')
            ->get()
            ->mapWithKeys(function ($payment) {
                $key = $payment->student_id . '-' . $payment->fee_structure_id;
                return [$key => (float) $payment->total_paid];
            });

        return $students->map(function ($student) use ($structures, $paymentMap) {
            $structureKey = $student->class_id . '-' . $student->academic_year_id;
            $studentStructures = $structures->get($structureKey, collect());

            $totalAssigned = 0.0;
            $totalPaid = 0.0;
            $totalDue = 0.0;
            $dueBreakdown = [];

            foreach ($studentStructures as $structure) {
                $assigned = (float) $structure->amount;
                $paidKey = $student->id . '-' . $structure->id;
                $paid = min($assigned, (float) ($paymentMap[$paidKey] ?? 0.0));
                $due = max(0, $assigned - $paid);

                $totalAssigned += $assigned;
                $totalPaid += $paid;
                $totalDue += $due;

                if ($due > 0) {
                    $dueBreakdown[] = [
                        'fee_head' => $structure->feeCategory?->name ?? 'N/A',
                        'due_amount' => $due,
                    ];
                }
            }

            return [
                'student' => $student,
                'total_assigned' => $totalAssigned,
                'total_paid' => $totalPaid,
                'total_due' => $totalDue,
                'due_heads' => count($dueBreakdown),
                'breakdown' => collect($dueBreakdown)->sortByDesc('due_amount')->values(),
            ];
        })
            ->filter(fn ($row) => $row['total_due'] > 0)
            ->sortByDesc('total_due')
            ->values();
    }

    private function userCanAccessStudent($user, Student $student): bool
    {
        if ($user->isParent()) {
            return (int) $student->parent_user_id === (int) $user->id;
        }

        if ($user->isStudent()) {
            return (string) $student->email === (string) $user->email;
        }

        return false;
    }

    private function mapLegacyPaymentMethod(string $paymentChannel): string
    {
        return match ($paymentChannel) {
            'upi' => 'online',
            'cash' => 'cash',
            'cheque' => 'cheque',
            default => 'bank_transfer',
        };
    }

    private function studentFullNameExpression(): string
    {
        return DB::connection()->getDriverName() === 'sqlite'
            ? "(COALESCE(first_name, '') || ' ' || COALESCE(last_name, ''))"
            : "CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, ''))";
    }

    private function recordDiscountIfNeeded(FeePayment $payment, float|int|string|null $discountAmount, ?string $remarks = null): void
    {
        $discount = (float) ($discountAmount ?? 0);

        if ($discount <= 0) {
            return;
        }

        FeeDiscountRecord::create([
            'fee_payment_id' => $payment->id,
            'student_id' => $payment->student_id,
            'fee_structure_id' => $payment->fee_structure_id,
            'discount_amount' => $discount,
            'remarks' => $remarks,
            'created_by' => auth()->id(),
        ]);

        $this->notifyAdminsAboutDiscount($payment, $discount, $remarks);
    }

    private function notifyAdminsAboutDiscount(FeePayment $payment, float $discount, ?string $remarks = null): void
    {
        $settings = NotificationSetting::first();
        if (!$settings || !$settings->mail_enabled) {
            return;
        }

        $adminEmails = \App\Models\User::query()
            ->where('role', 'admin')
            ->whereNotNull('email')
            ->pluck('email')
            ->filter()
            ->unique()
            ->values();

        if ($adminEmails->isEmpty()) {
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

            $payment->loadMissing(['student:id,first_name,last_name,admission_no', 'feeStructure.feeCategory:id,name']);

            $message = "A fee discount has been given.\n\n"
                . 'Student: ' . ($payment->student?->full_name ?? 'N/A') . "\n"
                . 'Admission No: ' . ($payment->student?->admission_no ?? '-') . "\n"
                . 'Fee Head: ' . ($payment->feeStructure?->feeCategory?->name ?? '-') . "\n"
                . 'Discount: Rs ' . number_format($discount, 2) . "\n"
                . 'Payment Receipt: ' . ($payment->receipt_no ?? '-') . "\n"
                . 'Remarks: ' . ($remarks ?: '-') . "\n";

            Mail::raw($message, function ($mail) use ($adminEmails) {
                $mail->to($adminEmails->all())
                    ->subject('Fee Discount Alert');
            });
        } catch (Throwable $exception) {
            // Do not block payment recording if notification delivery fails.
        }
    }
}

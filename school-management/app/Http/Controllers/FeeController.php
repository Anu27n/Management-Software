<?php

namespace App\Http\Controllers;

use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\FeePayment;
use App\Models\PaymentGatewaySetting;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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
        if ($request->filled('status')) {
            $query->where('status', $request->status);
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

    public function createPayment()
    {
        $students = Student::where('status', 'active')->get();
        $structures = FeeStructure::with(['feeCategory', 'schoolClass'])->get();
        $gatewaySettings = PaymentGatewaySetting::firstOrCreate(
            ['provider' => 'razorpay'],
            ['is_enabled' => false, 'currency' => 'INR']
        );

        return view('fees.create-payment', compact('students', 'structures', 'gatewaySettings'));
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
            'payment_method' => 'required|in:cash,online,cheque,bank_transfer',
            'transaction_id' => 'nullable|string|max:255',
            'razorpay_order_id' => 'nullable|string|max:255',
            'razorpay_payment_id' => 'nullable|string|max:255',
            'razorpay_signature' => 'nullable|string|max:255',
            'status' => 'required|in:paid,partial,pending',
            'remarks' => 'nullable|string',
        ]);

        if ($validated['payment_method'] === 'online') {
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

        FeePayment::create($validated);
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
        $structures = FeeStructure::with('feeCategory')
            ->where('class_id', $student->class_id)
            ->where('academic_year_id', $student->academic_year_id)
            ->get();

        return response()->json($structures);
    }

    private function isValidRazorpaySignature(string $orderId, string $paymentId, string $signature, string $secret): bool
    {
        $payload = $orderId . '|' . $paymentId;
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }
}

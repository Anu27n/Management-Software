<?php

namespace App\Http\Controllers;

use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\FeePayment;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

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
        return view('fees.create-payment', compact('students', 'structures'));
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
            'status' => 'required|in:paid,partial,pending',
            'remarks' => 'nullable|string',
        ]);

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

    public function getStudentFees(Student $student)
    {
        $structures = FeeStructure::with('feeCategory')
            ->where('class_id', $student->class_id)
            ->where('academic_year_id', $student->academic_year_id)
            ->get();

        return response()->json($structures);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeePayment extends Model
{
    protected $fillable = [
        'student_id', 'fee_structure_id', 'amount_paid', 'discount', 'fine',
        'payment_date', 'payment_method', 'transaction_id', 'receipt_no',
        'status', 'remarks', 'collected_by',
    ];

    protected $casts = [
        'amount_paid' => 'decimal:2',
        'discount' => 'decimal:2',
        'fine' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }

    public function collector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }
}

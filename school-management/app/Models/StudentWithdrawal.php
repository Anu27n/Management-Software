<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentWithdrawal extends Model
{
    protected $fillable = [
        'student_id',
        'withdrawal_date',
        'reason',
        'tc_issued',
        'tc_number',
        'tc_date',
        'security_refunded',
        'security_amount',
        'security_receipt_number',
        'refund_amount',
        'refund_date',
        'payment_mode',
        'utr_number',
        'cheque_number',
        'remarks',
        'processed_by',
    ];

    protected $casts = [
        'withdrawal_date' => 'date',
        'tc_issued' => 'boolean',
        'tc_date' => 'date',
        'security_refunded' => 'boolean',
        'security_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'refund_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}

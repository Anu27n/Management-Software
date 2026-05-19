<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeDiscountRecord extends Model
{
    protected $fillable = [
        'fee_payment_id',
        'student_id',
        'fee_structure_id',
        'discount_amount',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'discount_amount' => 'decimal:2',
    ];

    protected $appends = [
        'discount_percentage',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(FeePayment::class, 'fee_payment_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getDiscountPercentageAttribute(): float
    {
        $baseAmount = (float) ($this->feeStructure?->amount ?? 0);

        if ($baseAmount <= 0) {
            return 0.0;
        }

        return round((((float) $this->discount_amount) / $baseAmount) * 100, 2);
    }
}

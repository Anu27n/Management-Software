<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PreviousSessionDue extends Model
{
    protected $fillable = [
        'student_id',
        'previous_session',
        'due_amount',
        'status',
        'settled_at',
        'remarks',
        'created_by',
    ];

    protected $casts = [
        'due_amount' => 'decimal:2',
        'settled_at' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

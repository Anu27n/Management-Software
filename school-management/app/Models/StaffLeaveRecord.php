<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StaffLeaveRecord extends Model
{
    protected $fillable = [
        'staff_id',
        'from_date',
        'to_date',
        'reason',
        'remarks',
        'status',
        'approved_by',
        'responded_at',
    ];

    protected $casts = [
        'from_date' => 'date',
        'to_date' => 'date',
        'responded_at' => 'datetime',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function substituteAssignments(): HasMany
    {
        return $this->hasMany(SubstituteAssignment::class);
    }
}

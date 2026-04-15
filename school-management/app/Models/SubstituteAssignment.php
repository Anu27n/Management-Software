<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubstituteAssignment extends Model
{
    protected $fillable = [
        'staff_leave_record_id',
        'timetable_entry_id',
        'absent_staff_id',
        'substitute_staff_id',
        'cover_date',
        'status',
        'auto_assigned',
        'notes',
    ];

    protected $casts = [
        'cover_date' => 'date',
        'auto_assigned' => 'boolean',
    ];

    public function staffLeaveRecord(): BelongsTo
    {
        return $this->belongsTo(StaffLeaveRecord::class);
    }

    public function timetableEntry(): BelongsTo
    {
        return $this->belongsTo(TimetableEntry::class);
    }

    public function absentStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'absent_staff_id');
    }

    public function substituteStaff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'substitute_staff_id');
    }
}

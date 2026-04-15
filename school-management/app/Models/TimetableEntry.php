<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimetableEntry extends Model
{
    protected $fillable = [
        'academic_year_id',
        'class_id',
        'section_id',
        'subject_id',
        'teacher_id',
        'slot_id',
        'day_of_week',
        'room',
        'is_auto_generated',
    ];

    protected $casts = [
        'is_auto_generated' => 'boolean',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(TimetableSlot::class, 'slot_id');
    }
}

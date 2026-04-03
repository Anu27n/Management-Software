<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentExamReport extends Model
{
    protected $fillable = [
        'exam_id',
        'student_id',
        'class_id',
        'section_id',
        'remarks_unit_test',
        'remarks_main_exam',
        'personal_attributes',
        'final_result',
        'promoted_to_class_id',
        'school_reopens_on',
        'school_timings',
        'class_teacher_signature',
        'principal_signature',
        'parent_signature',
    ];

    protected $casts = [
        'personal_attributes' => 'array',
        'school_reopens_on' => 'date',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function promotedToClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'promoted_to_class_id');
    }
}

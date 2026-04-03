<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    protected $fillable = ['name', 'academic_year_id', 'start_date', 'end_date', 'report_template', 'term_number'];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'term_number' => 'integer',
    ];

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(StudentExamReport::class);
    }

    public function getResolvedTemplateAttribute(): string
    {
        if (filled($this->report_template)) {
            return $this->report_template;
        }

        $name = Str::lower($this->name);

        return Str::contains($name, ['final', 'annual', '2nd', 'second']) ? 'semester_2' : 'semester_1';
    }
}

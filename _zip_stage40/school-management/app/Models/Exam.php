<?php

namespace App\Models;

use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Support\ReportTemplateRegistry;

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

        if (Str::contains($name, ['round 1'])) {
            return 'unit_test_round_1_9_12';
        }

        if (Str::contains($name, ['round 2'])) {
            return 'unit_test_round_2_9_12';
        }

        if (ReportTemplateRegistry::isSupported($this->report_template)) {
            return $this->report_template;
        }

        return Str::contains($name, ['final', 'annual', '2nd', 'second']) ? 'semester_2' : 'semester_1';
    }
}

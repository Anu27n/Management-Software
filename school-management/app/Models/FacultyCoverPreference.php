<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacultyCoverPreference extends Model
{
    protected $fillable = [
        'staff_id',
        'max_daily_covers',
        'exclude_from_cover',
    ];

    protected $casts = [
        'exclude_from_cover' => 'boolean',
    ];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id');
    }
}

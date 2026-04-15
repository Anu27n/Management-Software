<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TimetableSlot extends Model
{
    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'display_order',
        'is_break',
    ];

    protected $casts = [
        'is_break' => 'boolean',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(TimetableEntry::class, 'slot_id');
    }
}

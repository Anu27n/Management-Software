<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSetting extends Model
{
    protected $fillable = [
        'school_name',
        'address',
        'contact_number',
        'contact_email',
        'logo_path',
        'favicon_path',
        'border_color',
        'header_fill_color',
        'title_bar_color',
        'title_text_color',
        'school_name_color',
        'page_text_color',
    ];

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'school_name' => config('app.name', 'School Management System'),
            'border_color' => '#7a4a00',
            'header_fill_color' => '#e8d5a3',
            'title_bar_color' => '#b8860b',
            'title_text_color' => '#ffffff',
            'school_name_color' => '#8b0000',
            'page_text_color' => '#1a0a00',
        ]);
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path ? route('site-assets.logo') : null;
    }

    public function getFaviconUrlAttribute(): ?string
    {
        return $this->favicon_path ? route('site-assets.favicon') : null;
    }
}

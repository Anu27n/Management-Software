<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;

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
        'app_primary_color',
        'app_primary_dark_color',
        'app_sidebar_bg_color',
        'app_sidebar_text_color',
        'app_sidebar_active_color',
        'app_background_color',
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
            'app_primary_color' => '#0f6b56',
            'app_primary_dark_color' => '#0b5443',
            'app_sidebar_bg_color' => '#031814',
            'app_sidebar_text_color' => '#9fcfc3',
            'app_sidebar_active_color' => '#14a27f',
            'app_background_color' => '#eef6f3',
        ]);
    }

    public function getLogoUrlAttribute(): ?string
    {
        if (!$this->logo_path) {
            return null;
        }

        return Route::has('site-assets.logo')
            ? route('site-assets.logo')
            : asset('storage/' . $this->logo_path);
    }

    public function getFaviconUrlAttribute(): ?string
    {
        if (!$this->favicon_path) {
            return null;
        }

        return Route::has('site-assets.favicon')
            ? route('site-assets.favicon')
            : asset('storage/' . $this->favicon_path);
    }
}

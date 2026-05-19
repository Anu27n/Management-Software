<?php

namespace App\Providers;

use App\Models\SiteSetting;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrap();

        View::composer('*', function ($view) {
            try {
                $settings = Schema::hasTable('site_settings')
                    ? SiteSetting::current()
                    : (object) [
                        'school_name' => config('app.name', 'School Management System'),
                        'address' => null,
                        'contact_number' => null,
                        'contact_email' => null,
                        'logo_path' => null,
                        'favicon_path' => null,
                        'logo_url' => null,
                        'favicon_url' => null,
                    ];
            } catch (Throwable) {
                $settings = (object) [
                    'school_name' => config('app.name', 'School Management System'),
                    'address' => null,
                    'contact_number' => null,
                    'contact_email' => null,
                    'logo_path' => null,
                    'favicon_path' => null,
                    'logo_url' => null,
                    'favicon_url' => null,
                ];
            }

            $view->with('siteSettings', $settings);
        });
    }
}

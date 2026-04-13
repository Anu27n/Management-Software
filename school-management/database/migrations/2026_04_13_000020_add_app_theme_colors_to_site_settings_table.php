<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('app_primary_color', 20)->nullable()->after('page_text_color');
            $table->string('app_primary_dark_color', 20)->nullable()->after('app_primary_color');
            $table->string('app_sidebar_bg_color', 20)->nullable()->after('app_primary_dark_color');
            $table->string('app_sidebar_text_color', 20)->nullable()->after('app_sidebar_bg_color');
            $table->string('app_sidebar_active_color', 20)->nullable()->after('app_sidebar_text_color');
            $table->string('app_background_color', 20)->nullable()->after('app_sidebar_active_color');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'app_primary_color',
                'app_primary_dark_color',
                'app_sidebar_bg_color',
                'app_sidebar_text_color',
                'app_sidebar_active_color',
                'app_background_color',
            ]);
        });
    }
};

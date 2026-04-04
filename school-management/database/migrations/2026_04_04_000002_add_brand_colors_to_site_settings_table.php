<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('border_color', 20)->nullable()->after('favicon_path');
            $table->string('header_fill_color', 20)->nullable()->after('border_color');
            $table->string('title_bar_color', 20)->nullable()->after('header_fill_color');
            $table->string('title_text_color', 20)->nullable()->after('title_bar_color');
            $table->string('school_name_color', 20)->nullable()->after('title_text_color');
            $table->string('page_text_color', 20)->nullable()->after('school_name_color');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'border_color',
                'header_fill_color',
                'title_bar_color',
                'title_text_color',
                'school_name_color',
                'page_text_color',
            ]);
        });
    }
};

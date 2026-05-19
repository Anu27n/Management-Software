<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->boolean('has_guardian')->default(false)->after('transport_mode');
            $table->string('guardian_relation')->nullable()->after('guardian_name');
            $table->boolean('has_siblings')->default(false)->after('mother_mobile');
            $table->unsignedInteger('sibling_count')->default(0)->after('has_siblings');
            $table->json('sibling_details')->nullable()->after('sibling_count');
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'has_guardian',
                'guardian_relation',
                'has_siblings',
                'sibling_count',
                'sibling_details',
            ]);
        });
    }
};

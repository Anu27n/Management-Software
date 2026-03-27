<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('students') || Schema::hasColumn('students', 'student_user_id')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            $table->foreignId('student_user_id')
                ->nullable()
                ->unique()
                ->after('parent_user_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('students') || !Schema::hasColumn('students', 'student_user_id')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            $table->dropConstrainedForeignId('student_user_id');
        });
    }
};

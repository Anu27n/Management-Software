<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            if (!Schema::hasColumn('subjects', 'category')) {
                $table->string('category')->default('scholastic')->after('class_id');
            }

            if (!Schema::hasColumn('subjects', 'display_order')) {
                $table->unsignedInteger('display_order')->default(0)->after('category');
            }
        });

        Schema::table('exams', function (Blueprint $table) {
            if (!Schema::hasColumn('exams', 'report_template')) {
                $table->string('report_template')->nullable()->after('name');
            }

            if (!Schema::hasColumn('exams', 'term_number')) {
                $table->unsignedTinyInteger('term_number')->nullable()->after('report_template');
            }
        });

        Schema::table('exam_results', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_results', 'unit_test_marks')) {
                $table->decimal('unit_test_marks', 5, 2)->nullable()->after('class_id');
            }

            if (!Schema::hasColumn('exam_results', 'main_exam_marks')) {
                $table->decimal('main_exam_marks', 5, 2)->nullable()->after('unit_test_marks');
            }

            if (!Schema::hasColumn('exam_results', 'calculated_total')) {
                $table->decimal('calculated_total', 5, 2)->nullable()->after('main_exam_marks');
            }

            if (!Schema::hasColumn('exam_results', 'subject_category')) {
                $table->string('subject_category')->default('scholastic')->after('calculated_total');
            }
        });

        Schema::create('student_exam_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('classes')->cascadeOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
            $table->text('remarks_unit_test')->nullable();
            $table->text('remarks_main_exam')->nullable();
            $table->json('personal_attributes')->nullable();
            $table->string('final_result')->nullable();
            $table->foreignId('promoted_to_class_id')->nullable()->constrained('classes')->nullOnDelete();
            $table->date('school_reopens_on')->nullable();
            $table->string('school_timings')->nullable();
            $table->string('class_teacher_signature')->nullable();
            $table->string('principal_signature')->nullable();
            $table->string('parent_signature')->nullable();
            $table->timestamps();

            $table->unique(['exam_id', 'student_id']);
            $table->index(['class_id', 'section_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_exam_reports');

        Schema::table('exam_results', function (Blueprint $table) {
            $dropColumns = array_filter([
                Schema::hasColumn('exam_results', 'unit_test_marks') ? 'unit_test_marks' : null,
                Schema::hasColumn('exam_results', 'main_exam_marks') ? 'main_exam_marks' : null,
                Schema::hasColumn('exam_results', 'calculated_total') ? 'calculated_total' : null,
                Schema::hasColumn('exam_results', 'subject_category') ? 'subject_category' : null,
            ]);

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });

        Schema::table('exams', function (Blueprint $table) {
            $dropColumns = array_filter([
                Schema::hasColumn('exams', 'report_template') ? 'report_template' : null,
                Schema::hasColumn('exams', 'term_number') ? 'term_number' : null,
            ]);

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });

        Schema::table('subjects', function (Blueprint $table) {
            $dropColumns = array_filter([
                Schema::hasColumn('subjects', 'category') ? 'category' : null,
                Schema::hasColumn('subjects', 'display_order') ? 'display_order' : null,
            ]);

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};

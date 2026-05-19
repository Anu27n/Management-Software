<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('student_s_no')->nullable();
            $table->string('student_surname')->nullable();
            $table->string('student_first_name');
            $table->string('student_middle_name')->nullable();
            $table->string('nationality')->nullable();
            $table->string('aadhaar_number', 12)->nullable()->index();
            $table->string('student_pen_number')->nullable();
            $table->string('category', 20)->nullable();
            $table->string('class_applied_for')->nullable();

            $table->text('residential_address')->nullable();
            $table->string('father_mobile_number', 20)->nullable();
            $table->string('mother_mobile_number', 20)->nullable();

            $table->string('last_school_name')->nullable();
            $table->string('last_class')->nullable();
            $table->boolean('report_card_attached')->default(false);
            $table->boolean('transfer_certificate_attached')->default(false);

            $table->string('is_child_healthy', 10)->default('yes');
            $table->boolean('health_report_attached')->default(false);

            $table->string('father_name')->nullable();
            $table->string('father_education')->nullable();
            $table->string('father_medium_of_instruction')->nullable();
            $table->string('father_occupation')->nullable();
            $table->string('father_business_designation')->nullable();
            $table->string('father_organization_name')->nullable();
            $table->text('father_office_address')->nullable();
            $table->string('father_phone', 20)->nullable();
            $table->string('father_email')->nullable();

            $table->string('mother_name')->nullable();
            $table->string('mother_education')->nullable();
            $table->string('mother_medium_of_instruction')->nullable();
            $table->string('mother_occupation')->nullable();
            $table->string('mother_business_designation')->nullable();
            $table->string('mother_organization_name')->nullable();
            $table->text('mother_office_address')->nullable();
            $table->string('mother_phone', 20)->nullable();
            $table->string('mother_email')->nullable();

            $table->string('parent_guardian_signature')->nullable();
            $table->date('declaration_date')->nullable();

            $table->string('student_name')->nullable();
            $table->string('personal_record_class')->nullable();
            $table->string('personal_record_section')->nullable();
            $table->string('house')->nullable();
            $table->string('blood_group', 10)->nullable();
            $table->decimal('height_cm', 6, 2)->nullable();
            $table->decimal('weight_kg', 6, 2)->nullable();

            $table->string('transport_mode', 20)->nullable();

            $table->string('guardian_name')->nullable();
            $table->string('phone_number', 20)->nullable();
            $table->text('office_address')->nullable();
            $table->string('father_mobile', 20)->nullable();
            $table->string('mother_mobile', 20)->nullable();

            $table->string('sibling_1_name')->nullable();
            $table->string('sibling_1_class')->nullable();
            $table->string('sibling_2_name')->nullable();
            $table->string('sibling_2_class')->nullable();

            $table->string('bpl_beneficiary', 10)->nullable();
            $table->string('father_signature')->nullable();
            $table->string('mother_signature')->nullable();
            $table->string('guardian_signature')->nullable();

            $table->string('registration_receipt_number')->nullable();
            $table->decimal('registration_amount', 10, 2)->nullable();
            $table->string('class_section_allotted')->nullable();
            $table->date('date_of_admission')->nullable();
            $table->string('fee_booklet_number')->nullable();
            $table->string('security_receipt_number')->nullable();
            $table->decimal('security_amount', 10, 2)->nullable();
            $table->text('remarks')->nullable();
            $table->string('principal_signature')->nullable();
            $table->string('office_incharge_signature')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};

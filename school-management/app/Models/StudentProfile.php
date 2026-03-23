<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentProfile extends Model
{
    protected $fillable = [
        'student_id',
        'student_s_no',
        'student_surname',
        'student_first_name',
        'student_middle_name',
        'nationality',
        'aadhaar_number',
        'student_pen_number',
        'category',
        'class_applied_for',
        'residential_address',
        'father_mobile_number',
        'mother_mobile_number',
        'last_school_name',
        'last_class',
        'report_card_attached',
        'transfer_certificate_attached',
        'is_child_healthy',
        'health_report_attached',
        'father_name',
        'father_education',
        'father_medium_of_instruction',
        'father_occupation',
        'father_business_designation',
        'father_organization_name',
        'father_office_address',
        'father_phone',
        'father_email',
        'mother_name',
        'mother_education',
        'mother_medium_of_instruction',
        'mother_occupation',
        'mother_business_designation',
        'mother_organization_name',
        'mother_office_address',
        'mother_phone',
        'mother_email',
        'parent_guardian_signature',
        'declaration_date',
        'student_name',
        'personal_record_class',
        'personal_record_section',
        'house',
        'blood_group',
        'height_cm',
        'weight_kg',
        'transport_mode',
        'guardian_name',
        'phone_number',
        'office_address',
        'father_mobile',
        'mother_mobile',
        'sibling_1_name',
        'sibling_1_class',
        'sibling_2_name',
        'sibling_2_class',
        'bpl_beneficiary',
        'father_signature',
        'mother_signature',
        'guardian_signature',
        'registration_receipt_number',
        'registration_amount',
        'class_section_allotted',
        'date_of_admission',
        'fee_booklet_number',
        'security_receipt_number',
        'security_amount',
        'remarks',
        'principal_signature',
        'office_incharge_signature',
    ];

    protected $casts = [
        'report_card_attached' => 'boolean',
        'transfer_certificate_attached' => 'boolean',
        'health_report_attached' => 'boolean',
        'declaration_date' => 'date',
        'date_of_admission' => 'date',
        'registration_amount' => 'decimal:2',
        'security_amount' => 'decimal:2',
        'height_cm' => 'decimal:2',
        'weight_kg' => 'decimal:2',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}

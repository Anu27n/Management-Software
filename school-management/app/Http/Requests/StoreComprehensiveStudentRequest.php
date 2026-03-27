<?php

namespace App\Http\Requests;

use App\Models\SchoolClass;
use App\Support\ClassEligibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreComprehensiveStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_s_no' => ['required', 'string', 'max:50', 'unique:students,admission_no'],
            'student_surname' => ['nullable', 'string', 'max:100'],
            'student_first_name' => ['required', 'string', 'max:100'],
            'student_middle_name' => ['nullable', 'string', 'max:100'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'nationality' => ['required', 'string', 'max:100'],
            'aadhaar_number' => ['required', 'regex:/^[0-9]{12}$/'],
            'student_pen_number' => ['nullable', 'string', 'max:100'],
            'category' => ['required', Rule::in(['GEN', 'SC', 'ST', 'OBC'])],

            'class_id' => ['required', 'exists:classes,id'],
            'section_id' => ['required', 'exists:sections,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'admission_date' => ['required', 'date'],

            'residential_address' => ['required', 'string', 'max:1000'],
            'father_mobile_number' => ['required', 'regex:/^[0-9]{10}$/'],
            'mother_mobile_number' => ['required', 'regex:/^[0-9]{10}$/'],

            'last_school_name' => ['nullable', 'string', 'max:255'],
            'last_class' => ['nullable', 'string', 'max:100'],
            'report_card_attached' => ['required', 'boolean'],
            'transfer_certificate_attached' => ['required', 'boolean'],

            'is_child_healthy' => ['required', Rule::in(['yes', 'no'])],
            'health_report_attached' => ['required', 'boolean'],

            'father_name' => ['required', 'string', 'max:255'],
            'father_education' => ['nullable', 'string', 'max:255'],
            'father_medium_of_instruction' => ['nullable', 'string', 'max:255'],
            'father_occupation' => ['nullable', 'string', 'max:255'],
            'father_business_designation' => ['nullable', 'string', 'max:255'],
            'father_organization_name' => ['nullable', 'string', 'max:255'],
            'father_office_address' => ['nullable', 'string', 'max:500'],
            'father_phone' => ['nullable', 'regex:/^[0-9]{10}$/'],
            'father_email' => ['nullable', 'email', 'max:255'],

            'mother_name' => ['required', 'string', 'max:255'],
            'mother_education' => ['nullable', 'string', 'max:255'],
            'mother_medium_of_instruction' => ['nullable', 'string', 'max:255'],
            'mother_occupation' => ['nullable', 'string', 'max:255'],
            'mother_business_designation' => ['nullable', 'string', 'max:255'],
            'mother_organization_name' => ['nullable', 'string', 'max:255'],
            'mother_office_address' => ['nullable', 'string', 'max:500'],
            'mother_phone' => ['nullable', 'regex:/^[0-9]{10}$/'],
            'mother_email' => ['nullable', 'email', 'max:255'],

            'house' => ['nullable', 'string', 'max:100'],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'height_cm' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'weight_kg' => ['nullable', 'numeric', 'min:0', 'max:300'],

            'transport_mode' => ['required', Rule::in(['parents', 'van', 'auto', 'rickshaw', 'self'])],

            'guardian_name' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'regex:/^[0-9]{10}$/'],
            'office_address' => ['nullable', 'string', 'max:500'],
            'father_mobile' => ['nullable', 'regex:/^[0-9]{10}$/'],
            'mother_mobile' => ['nullable', 'regex:/^[0-9]{10}$/'],

            'sibling_1_name' => ['nullable', 'string', 'max:255'],
            'sibling_1_class' => ['nullable', 'string', 'max:100'],
            'sibling_2_name' => ['nullable', 'string', 'max:255'],
            'sibling_2_class' => ['nullable', 'string', 'max:100'],

            'bpl_beneficiary' => ['nullable', Rule::in(['yes', 'no', 'na'])],
            'rte' => ['nullable', Rule::in(['yes', 'no'])],
            'father_signature' => ['nullable', 'string', 'max:255'],
            'mother_signature' => ['nullable', 'string', 'max:255'],
            'guardian_signature' => ['nullable', 'string', 'max:255'],

            'registration_receipt_number' => ['nullable', 'string', 'max:100'],
            'registration_amount' => ['nullable', 'numeric', 'min:0'],
            'class_section_allotted' => ['nullable', 'string', 'max:100'],
            'date_of_admission' => ['nullable', 'date'],
            'fee_booklet_number' => ['nullable', 'string', 'max:100'],
            'security_receipt_number' => ['nullable', 'string', 'max:100'],
            'security_amount' => ['nullable', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'principal_signature' => ['nullable', 'string', 'max:255'],
            'office_incharge_signature' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'student_s_no' => 'student serial number',
            'aadhaar_number' => 'Aadhaar number',
            'father_mobile_number' => 'father mobile number',
            'mother_mobile_number' => 'mother mobile number',
            'rte' => 'RTE',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $classId = $this->input('class_id');

            if (!$classId) {
                return;
            }

            $className = SchoolClass::query()->whereKey($classId)->value('name');
            $isRteEligible = ClassEligibility::isRteEligible($className);
            $rte = $this->input('rte');

            if ($isRteEligible && blank($rte)) {
                $validator->errors()->add('rte', 'The RTE field is required for classes up to 8th.');
            }

            if (!$isRteEligible && filled($rte)) {
                $validator->errors()->add('rte', 'The RTE field is only allowed for classes up to 8th.');
            }
        });
    }
}

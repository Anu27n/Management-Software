<?php

namespace App\Http\Requests;

use App\Models\SchoolClass;
use App\Models\Student;
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
        $qualificationOptions = ['illiterate', 'school', 'diploma', 'graduate', 'postgraduate', 'doctorate', 'other'];
        $mediumOptions = ['Hindi', 'English'];
        $fatherOccupationOptions = ['Private Job', 'Government Job', 'Business', 'Professional', 'Unemployed'];
        $motherOccupationOptions = ['Private Job', 'Government Job', 'Business', 'Professional', 'Housewife'];

        return [
            'student_s_no' => ['nullable', 'string', 'max:50', 'unique:students,admission_no'],
            'student_surname' => ['nullable', 'string', 'max:100'],
            'student_first_name' => ['required', 'string', 'max:100'],
            'student_middle_name' => ['nullable', 'string', 'max:100'],
            'gender' => ['required', Rule::in(['male', 'female'])],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'nationality' => ['required', 'string', 'max:100'],
            'aadhaar_number' => ['nullable', 'regex:/^[0-9]{12}$/'],
            'student_pen_number' => ['nullable', 'string', 'max:100'],
            'category' => ['required', Rule::in(['GEN', 'SC', 'ST', 'OBC'])],
            'bpl_beneficiary' => ['nullable', Rule::in(['yes', 'no', 'na'])],

            'class_id' => ['required', 'exists:classes,id'],
            'section_id' => ['required', 'exists:sections,id'],
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'admission_date' => ['required', 'date'],

            'residential_address' => ['required', 'string', 'max:1000'],
            'father_mobile_number' => ['nullable', 'regex:/^[0-9]{10}$/'],
            'mother_mobile_number' => ['nullable', 'regex:/^[0-9]{10}$/'],

            'last_school_name' => ['nullable', 'string', 'max:255'],
            'last_class' => ['nullable', 'string', 'max:100'],
            'report_card_attached' => ['required', 'boolean'],
            'transfer_certificate_attached' => ['required', 'boolean'],

            'is_child_healthy' => ['nullable', Rule::in(['yes', 'no'])],
            'health_report_attached' => ['nullable', 'boolean'],

            'father_name' => ['nullable', 'string', 'max:255'],
            'father_education' => ['nullable', Rule::in($qualificationOptions)],
            'father_medium_of_instruction' => ['nullable', Rule::in($mediumOptions)],
            'father_occupation' => ['nullable', Rule::in($fatherOccupationOptions)],
            'father_business_designation' => ['nullable', 'string', 'max:255'],
            'father_organization_name' => ['nullable', 'string', 'max:255'],
            'father_office_address' => ['nullable', 'string', 'max:500'],
            'father_phone' => ['nullable', 'regex:/^[0-9]{10}$/'],
            'father_email' => ['nullable', 'email', 'max:255'],

            'mother_name' => ['nullable', 'string', 'max:255'],
            'mother_education' => ['nullable', Rule::in($qualificationOptions)],
            'mother_medium_of_instruction' => ['nullable', Rule::in($mediumOptions)],
            'mother_occupation' => ['nullable', Rule::in($motherOccupationOptions)],
            'mother_business_designation' => ['nullable', 'string', 'max:255'],
            'mother_organization_name' => ['nullable', 'string', 'max:255'],
            'mother_office_address' => ['nullable', 'string', 'max:500'],
            'mother_phone' => ['nullable', 'regex:/^[0-9]{10}$/'],
            'mother_email' => ['nullable', 'email', 'max:255'],

            'house' => ['nullable', 'string', 'max:100'],
            'blood_group' => ['nullable', 'string', 'max:10'],
            'height_cm' => ['nullable', 'numeric', 'min:0', 'max:300'],
            'weight_kg' => ['nullable', 'numeric', 'min:0', 'max:300'],

            'transport_mode' => ['nullable', Rule::in(['parents', 'van/auto/rickshaw', 'self'])],

            'has_guardian' => ['nullable', 'boolean'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'regex:/^[0-9]{10}$/'],
            'office_address' => ['nullable', 'string', 'max:500'],
            'guardian_relation' => ['nullable', 'string', 'max:100'],
            'father_mobile' => ['nullable', 'regex:/^[0-9]{10}$/'],
            'mother_mobile' => ['nullable', 'regex:/^[0-9]{10}$/'],

            'has_siblings' => ['nullable', 'boolean'],
            'sibling_count' => ['nullable', 'integer', 'min:0', 'max:10'],
            'sibling_details' => ['nullable', 'array'],
            'sibling_details.*.student_id' => ['nullable', 'exists:students,id'],
            'sibling_details.*.name' => ['nullable', 'string', 'max:255'],
            'sibling_details.*.is_studying' => ['nullable', 'boolean'],
            'sibling_details.*.class_id' => ['nullable', 'exists:classes,id'],
            'sibling_details.*.notes' => ['nullable', 'string', 'max:255'],
            'sibling_1_name' => ['nullable', 'string', 'max:255'],
            'sibling_1_class' => ['nullable', 'string', 'max:100'],
            'sibling_2_name' => ['nullable', 'string', 'max:255'],
            'sibling_2_class' => ['nullable', 'string', 'max:100'],

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
            $hasGuardian = $this->boolean('has_guardian');
            $hasSiblings = $this->boolean('has_siblings');

            if ($isRteEligible && blank($rte)) {
                $validator->errors()->add('rte', 'The RTE field is required for classes up to 8th.');
            }

            if (!$isRteEligible && filled($rte)) {
                $validator->errors()->add('rte', 'The RTE field is only allowed for classes up to 8th.');
            }

            if ($hasGuardian) {
                if (blank($this->input('guardian_name'))) {
                    $validator->errors()->add('guardian_name', 'Guardian name is required when guardian is marked Yes.');
                }

                if (blank($this->input('phone_number'))) {
                    $validator->errors()->add('phone_number', 'Guardian phone number is required when guardian is marked Yes.');
                }

                if (blank($this->input('office_address'))) {
                    $validator->errors()->add('office_address', 'Guardian address is required when guardian is marked Yes.');
                }

                if (blank($this->input('guardian_relation'))) {
                    $validator->errors()->add('guardian_relation', 'Guardian relation is required when guardian is marked Yes.');
                }
            }

            if ($hasSiblings) {
                $siblingCount = (int) $this->input('sibling_count', 0);
                $siblings = collect($this->input('sibling_details', []))->filter(fn ($item) => is_array($item));
                $selectedSiblingIds = $siblings
                    ->pluck('student_id')
                    ->filter(fn ($value) => filled($value))
                    ->map(fn ($value) => (int) $value)
                    ->values();
                $selectedSiblings = Student::query()
                    ->with('schoolClass:id,name')
                    ->whereIn('id', $selectedSiblingIds)
                    ->get()
                    ->keyBy('id');

                if ($siblingCount <= 0) {
                    $validator->errors()->add('sibling_count', 'Enter the number of siblings when siblings is marked Yes.');
                }

                if ($siblings->count() < $siblingCount) {
                    $validator->errors()->add('sibling_details', 'Enter all sibling details.');
                }

                $siblings->take($siblingCount)->each(function ($sibling, $index) use ($validator, $selectedSiblings) {
                    if (blank($sibling['name'] ?? null)) {
                        $validator->errors()->add("sibling_details.$index.name", 'Sibling name is required.');
                    }

                    if (!empty($sibling['is_studying']) && blank($sibling['class_id'] ?? null)) {
                        $validator->errors()->add("sibling_details.$index.class_id", 'Sibling class is required when studying is Yes.');
                    }

                    $siblingStudentId = filled($sibling['student_id'] ?? null) ? (int) $sibling['student_id'] : null;
                    if (!$siblingStudentId) {
                        return;
                    }

                    $linkedSibling = $selectedSiblings->get($siblingStudentId);
                    if (!$linkedSibling) {
                        $validator->errors()->add("sibling_details.$index.student_id", 'Selected sibling record was not found.');
                        return;
                    }

                    $incomingFather = $this->normalizePersonName($this->input('father_name'));
                    $incomingMother = $this->normalizePersonName($this->input('mother_name'));
                    $linkedFather = $this->normalizePersonName($linkedSibling->father_name);
                    $linkedMother = $this->normalizePersonName($linkedSibling->mother_name);

                    if ($incomingFather === '' || $incomingMother === '' || $linkedFather === '' || $linkedMother === '') {
                        $validator->errors()->add("sibling_details.$index.student_id", 'Assigned sibling requires matching father and mother names on both student records.');
                        return;
                    }

                    if ($incomingFather !== $linkedFather || $incomingMother !== $linkedMother) {
                        $validator->errors()->add("sibling_details.$index.student_id", 'Father name and mother name must match before assigning this sibling.');
                    }
                });

                if ($selectedSiblingIds->count() !== $selectedSiblingIds->unique()->count()) {
                    $validator->errors()->add('sibling_details', 'The same sibling cannot be assigned more than once.');
                }
            }
        });
    }

    private function normalizePersonName(?string $value): string
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', (string) $value)));
    }
}

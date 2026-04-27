<?php

/**
 * Default fee bootstrap (SchoolFeeStructureBootstrap / SchoolFeeStructureSeeder / migration 000024).
 *
 * CURRENT FIGURES — aligned with school brochure “₹ Fee Details” / one-time admission table
 * (e.g. fullstrucurefees.png, newadmissionfees.png): quarterly slabs, ₹1,000 annual misc, admission
 * components, and security by slab. “Total payable at admission” on the sheet is the sum of
 * one quarter + annual misc + all one-time lines (not stored as its own fee row).
 *
 * Admin UI: Settings → Default fee amounts (overrides this file when saved).
 *
 * FUTURE (inflation / new session): change amounts in the admin screen or update `tiers` / `amounts`
 * below (or .env overrides), then run for a year that does not already have quarterly structures seeded:
 *
 *   php artisan db:seed --class=SchoolFeeStructureSeeder
 *
 * Optional .env overrides (defaults in second argument if unset):
 *   SCHOOL_FEE_MISC_ANNUAL, SCHOOL_FEE_ADMISSION
 *   SCHOOL_FEE_Q1_I_VIII, SCHOOL_FEE_REG_I_VIII, SCHOOL_FEE_SEC_I_VIII
 *   SCHOOL_FEE_Q1_IX_X, SCHOOL_FEE_REG_IX_X, SCHOOL_FEE_SEC_IX_X
 *   SCHOOL_FEE_Q1_XI_XII, SCHOOL_FEE_REG_XI_XII, SCHOOL_FEE_SEC_XI_XII
 */
return [

    'amounts' => [
        // Brochure: Miscellaneous Charges (Annual) — same for I–VIII, IX–X, XI–XII
        'misc_annual' => (float) env('SCHOOL_FEE_MISC_ANNUAL', 1000),
        // Brochure: Admission Fees — ₹6,000 all slabs
        'admission_one_time' => (float) env('SCHOOL_FEE_ADMISSION', 6000),
    ],

    /**
     * Grade slabs matching brochure columns I–VIII | IX–X | XI–XII.
     * quarterly = per quarter; registration & security = new admission one-time for that slab.
     */
    'tiers' => [
        [
            'grade_min' => 1,
            'grade_max' => 8,
            // I–VIII: quarterly 9,500 | registration 500 | security 4,000
            'quarterly' => (float) env('SCHOOL_FEE_Q1_I_VIII', 9500),
            'registration' => (float) env('SCHOOL_FEE_REG_I_VIII', 500),
            'security' => (float) env('SCHOOL_FEE_SEC_I_VIII', 4000),
        ],
        [
            'grade_min' => 9,
            'grade_max' => 10,
            // IX–X: quarterly 10,500 | registration 600 | security 4,000
            'quarterly' => (float) env('SCHOOL_FEE_Q1_IX_X', 10500),
            'registration' => (float) env('SCHOOL_FEE_REG_IX_X', 600),
            'security' => (float) env('SCHOOL_FEE_SEC_IX_X', 4000),
        ],
        [
            'grade_min' => 11,
            'grade_max' => 12,
            // XI–XII: quarterly 11,500 | registration 600 | security 5,000
            'quarterly' => (float) env('SCHOOL_FEE_Q1_XI_XII', 11500),
            'registration' => (float) env('SCHOOL_FEE_REG_XI_XII', 600),
            'security' => (float) env('SCHOOL_FEE_SEC_XI_XII', 5000),
        ],
    ],

    'categories' => [
        'quarterly' => [
            'name' => 'Quarterly Fees (Every Quarter)',
            'description' => 'Brochure quarterly instalment amounts (edit config when fees change).',
        ],
        'misc' => [
            'name' => 'Miscellaneous Charges (Annual)',
            'description' => 'Annual miscellaneous',
        ],
        'registration' => [
            'name' => 'Registration & Prospectus',
            'description' => 'One-time at new admission',
        ],
        'admission' => [
            'name' => 'Admission Fees',
            'description' => 'One-time at new admission',
        ],
        'security' => [
            'name' => 'Security Deposit (Refundable)',
            'description' => 'One-time at new admission, refundable',
        ],
    ],

    /**
     * If any fee_structure for this academic year uses one of these category names, bootstrap skips
     * (avoids duplicates). Add a legacy name here if you rename the quarterly category.
     */
    'idempotency_quarterly_category_names' => [
        'Quarterly Fees (Every Quarter)',
        'Quarterly Fees',
    ],
];

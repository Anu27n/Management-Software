<?php

namespace Database\Seeders;

use App\Services\SchoolFeeStructureBootstrap;
use Illuminate\Database\Seeder;

class SchoolFeeStructureSeeder extends Seeder
{
    /**
     * Seeds fee categories + structures from config/school_fees.php for the active/latest academic year.
     * Idempotent: skips if quarterly structures already exist for that year.
     */
    public function run(): void
    {
        SchoolFeeStructureBootstrap::run();
    }
}

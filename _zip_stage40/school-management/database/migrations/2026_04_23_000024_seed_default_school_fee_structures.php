<?php

use App\Services\SchoolFeeStructureBootstrap;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('fee_structures') || !Schema::hasColumn('fee_structures', 'applies_to')) {
            return;
        }

        SchoolFeeStructureBootstrap::run();
    }

    public function down(): void
    {
        // Intentionally left blank: seed data may be edited in production.
    }
};

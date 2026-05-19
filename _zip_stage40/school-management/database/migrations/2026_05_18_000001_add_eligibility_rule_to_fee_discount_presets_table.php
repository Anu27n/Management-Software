<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_discount_presets', function (Blueprint $table) {
            if (!Schema::hasColumn('fee_discount_presets', 'eligibility_rule')) {
                $table->string('eligibility_rule', 30)->default('standard')->after('discount_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('fee_discount_presets', function (Blueprint $table) {
            if (Schema::hasColumn('fee_discount_presets', 'eligibility_rule')) {
                $table->dropColumn('eligibility_rule');
            }
        });
    }
};

<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Schema;

final class SchoolFeeConfig
{
    /**
     * Merges config/school_fees.php with optional overrides saved in site_settings.fee_bootstrap_defaults.
     * Admin edits on the website override the file for bootstrap / display purposes.
     *
     * @return array<string, mixed>
     */
    public static function resolved(): array
    {
        $base = config('school_fees');
        if (!is_array($base)) {
            return [];
        }

        if (!Schema::hasTable('site_settings') || !Schema::hasColumn('site_settings', 'fee_bootstrap_defaults')) {
            return $base;
        }

        $settings = SiteSetting::query()->first();
        $overrides = $settings?->fee_bootstrap_defaults;
        if (!is_array($overrides) || $overrides === []) {
            return $base;
        }

        return array_replace_recursive($base, $overrides);
    }
}

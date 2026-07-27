<?php

namespace App\Services;

use App\Models\Warranty;
use Illuminate\Support\Facades\DB;

class WarrantyReferenceGenerator
{
    public function generate(?int $year = null): string
    {
        $year ??= (int) now('Africa/Nairobi')->format('Y');
        $prefix = app(SettingsService::class)->get('warranty_reference_prefix', 'KEL-WTY');

        return DB::transaction(function () use ($year, $prefix) {
            $latest = Warranty::withTrashed()
                ->where('reference', 'like', "{$prefix}-{$year}-%")
                ->lockForUpdate()
                ->orderByDesc('reference')
                ->value('reference');

            $sequence = 1;
            if ($latest && preg_match('/-(\d+)$/', $latest, $matches)) {
                $sequence = ((int) $matches[1]) + 1;
            }

            return sprintf('%s-%d-%06d', $prefix, $year, $sequence);
        });
    }
}

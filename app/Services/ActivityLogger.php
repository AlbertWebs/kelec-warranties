<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Throwable;

class ActivityLogger
{
    /**
     * @param  array<string, mixed>|null  $meta
     */
    public function log(
        string $type,
        string $action,
        string $status,
        ?string $query = null,
        ?string $reference = null,
        ?string $resultSummary = null,
        ?array $meta = null,
    ): void {
        try {
            ActivityLog::query()->create([
                'type' => $type,
                'action' => $action,
                'status' => $status,
                'query' => $query !== null ? mb_substr($query, 0, 255) : null,
                'reference' => $reference !== null ? mb_substr($reference, 0, 255) : null,
                'result_summary' => $resultSummary !== null ? mb_substr($resultSummary, 0, 255) : null,
                'meta' => $meta,
                'ip_address' => Request::ip(),
                'user_agent' => Request::userAgent(),
            ]);
        } catch (Throwable $e) {
            Log::warning('Failed to write activity log', [
                'type' => $type,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

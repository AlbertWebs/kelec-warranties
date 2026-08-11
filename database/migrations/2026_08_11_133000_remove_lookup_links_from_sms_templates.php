<?php

use App\Models\NotificationTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NotificationTemplate::query()->each(function (NotificationTemplate $template): void {
            $body = (string) $template->sms_body;
            if ($body === '') {
                return;
            }

            $cleaned = preg_replace('/\s*Lookup:\s*\S+(?:\s*\([^)]*\))?/i', '', $body) ?? $body;
            $cleaned = preg_replace('/\s*Lookup\s+\{\{lookup_link\}\}/i', '', $cleaned) ?? $cleaned;
            $cleaned = preg_replace('/\s*Lookup\s+https?:\/\/\S+/i', '', $cleaned) ?? $cleaned;
            $cleaned = trim(preg_replace('/\s{2,}/', ' ', $cleaned) ?? $cleaned);

            if ($cleaned !== '' && $cleaned !== $body) {
                $template->update(['sms_body' => $cleaned]);
            }
        });
    }

    public function down(): void
    {
        // Intentionally empty — SMS lookup links should stay removed.
    }
};

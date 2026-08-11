<?php

use App\Models\NotificationTemplate;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        NotificationTemplate::query()->each(function (NotificationTemplate $template): void {
            $body = (string) $template->email_body;
            if ($body === '') {
                return;
            }

            $updated = preg_replace(
                '/^Support:\s*.+$/mi',
                'Support: {{support_phone}} / {{support_email}}',
                $body
            ) ?? $body;

            if ($updated !== $body) {
                $template->update(['email_body' => $updated]);
            }
        });
    }

    public function down(): void
    {
        // No rollback — templates should keep settings placeholders.
    }
};

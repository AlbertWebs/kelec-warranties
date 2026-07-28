<?php

use App\Services\SmsService;

if (! function_exists('send_sms')) {
    /**
     * Send an SMS via AdvantaSMS.
     *
     * @return array{
     *     ok: bool,
     *     response?: string,
     *     message_id?: string|null,
     *     response_code?: int|null,
     *     response_description?: string|null,
     *     sms_log_id?: int|null
     * }
     */
    function send_sms(string $to, string $message, ?string $context = null, ?string $timeToSend = null): array
    {
        return app(SmsService::class)->send($to, $message, $context, $timeToSend);
    }
}

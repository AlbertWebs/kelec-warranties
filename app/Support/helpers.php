<?php

use App\Services\PhoneNumberService;
use App\Services\SettingsService;
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

if (! function_exists('support_phone')) {
    function support_phone(?string $default = '0716 052 243'): string
    {
        $raw = trim((string) app(SettingsService::class)->get('support_phone', $default));

        return $raw !== ''
            ? app(PhoneNumberService::class)->formatDisplay($raw)
            : (string) $default;
    }
}

if (! function_exists('support_phone_tel')) {
    function support_phone_tel(?string $default = '0716052243'): ?string
    {
        $raw = trim((string) app(SettingsService::class)->get('support_phone', $default));

        return app(PhoneNumberService::class)->toTelHref($raw !== '' ? $raw : $default);
    }
}

if (! function_exists('support_email')) {
    function support_email(?string $default = 'support@k-elec.co.ke'): string
    {
        $raw = trim((string) app(SettingsService::class)->get('support_email', $default));

        return $raw !== '' ? $raw : (string) $default;
    }
}

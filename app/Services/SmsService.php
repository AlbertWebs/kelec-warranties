<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsService
{
    public function __construct(protected SettingsService $settingsService) {}

    /**
     * @return array{ok: bool, response?: string}
     */
    public function send(string $to, string $message): array
    {
        if (! $this->settingsService->get('sms_enabled', false)) {
            // Mock/dev mode: treat as successful for local testing.
            Log::info('SMS mock send', ['to' => $to, 'message' => $message]);

            return ['ok' => true, 'response' => 'sms_mock_sent'];
        }

        $endpoint = (string) $this->settingsService->get('sms_endpoint');
        $method = strtoupper((string) $this->settingsService->get('sms_http_method', 'POST'));
        $timeout = (int) $this->settingsService->get('sms_timeout', 15);
        $phoneParam = (string) $this->settingsService->get('sms_phone_param', 'to');
        $messageParam = (string) $this->settingsService->get('sms_message_param', 'message');
        $apiKey = (string) $this->settingsService->get('sms_api_key');
        $senderId = (string) $this->settingsService->get('sms_sender_id');
        $authHeader = (string) $this->settingsService->get('sms_auth_header', 'Authorization');

        $payload = [
            $phoneParam => $to,
            $messageParam => $message,
        ];

        if ($senderId !== '') {
            $payload['sender_id'] = $senderId;
        }

        try {
            $request = Http::timeout($timeout);
            if ($apiKey !== '') {
                $request = $request->withHeaders([$authHeader => $apiKey]);
            }

            $response = $method === 'GET'
                ? $request->get($endpoint, $payload)
                : $request->post($endpoint, $payload);

            return [
                'ok' => $response->successful(),
                'response' => substr($response->body(), 0, 1000),
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'response' => $e->getMessage()];
        }
    }
}

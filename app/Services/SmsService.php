<?php

namespace App\Services;

use App\Models\SmsLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SmsService
{
    public const BASE_URL = 'https://quicksms.advantasms.com/api/services';

    /** @see https://www.advantasms.com/bulksms-api */
    public const RESPONSE_CODES = [
        200 => 'Successful request',
        1001 => 'Invalid sender id',
        1002 => 'Network not allowed',
        1003 => 'Invalid mobile number',
        1004 => 'Low bulk credits',
        1005 => 'Failed. System error',
        1006 => 'Invalid credentials',
        1007 => 'Failed. System error',
        1008 => 'No delivery report',
        1009 => 'Unsupported data type',
        1010 => 'Unsupported request type',
        4090 => 'Internal error. Try again after 5 minutes',
        4091 => 'No Partner ID is set',
        4092 => 'No API key provided',
        4093 => 'Details not found',
    ];

    public function __construct(
        protected SettingsService $settingsService,
        protected PhoneNumberService $phoneNumberService,
    ) {}

    /**
     * Generic AdvantaSMS send used across the system.
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
    public function send(string $to, string $message, ?string $context = null, ?string $timeToSend = null): array
    {
        $mobile = $this->phoneNumberService->normalize($to) ?? trim($to);
        $shortcode = (string) $this->settingsService->get('sms_sender_id', '');

        if (! $this->settingsService->get('sms_enabled', false)) {
            Log::info('SMS mock send', ['to' => $mobile, 'message' => $message, 'context' => $context]);

            $log = $this->createLog([
                'mobile' => $mobile,
                'message' => $message,
                'status' => 'mock',
                'shortcode' => $shortcode !== '' ? $shortcode : null,
                'context' => $context,
                'response_code' => 200,
                'response_description' => 'sms_mock_sent',
                'provider_response' => 'sms_mock_sent',
            ]);

            return [
                'ok' => true,
                'response' => 'sms_mock_sent',
                'message_id' => null,
                'response_code' => 200,
                'response_description' => 'sms_mock_sent',
                'sms_log_id' => $log->id,
            ];
        }

        $credentials = $this->credentials();
        if ($credentials === null) {
            $log = $this->createLog([
                'mobile' => $mobile,
                'message' => $message,
                'status' => 'failed',
                'shortcode' => $shortcode !== '' ? $shortcode : null,
                'context' => $context,
                'response_code' => 4092,
                'response_description' => 'SMS credentials incomplete',
                'provider_response' => 'SMS credentials incomplete (api key, partner ID, sender ID)',
            ]);

            return [
                'ok' => false,
                'response' => 'SMS credentials incomplete (api key, partner ID, sender ID)',
                'message_id' => null,
                'response_code' => 4092,
                'response_description' => 'SMS credentials incomplete',
                'sms_log_id' => $log->id,
            ];
        }

        $payload = [
            'apikey' => $credentials['apikey'],
            'partnerID' => $credentials['partnerID'],
            'message' => $message,
            'shortcode' => $credentials['shortcode'],
            'mobile' => $mobile,
        ];

        if ($timeToSend !== null && $timeToSend !== '') {
            $payload['timeToSend'] = $timeToSend;
        }

        try {
            $response = Http::timeout($credentials['timeout'])
                ->acceptJson()
                ->asJson()
                ->post($this->endpoint('sendsms'), $payload);

            $body = $response->json() ?? [];
            $first = $this->firstResponse($body);
            $code = $this->extractResponseCode($first);
            $description = $this->extractResponseDescription($first, $code);
            $messageId = isset($first['messageid']) ? (string) $first['messageid'] : null;
            $networkId = isset($first['networkid']) ? (string) $first['networkid'] : null;
            $ok = $response->successful() && $code === 200;
            $raw = Str::limit($response->body(), 1000);

            $log = $this->createLog([
                'mobile' => $mobile,
                'message' => $message,
                'status' => $ok ? 'sent' : 'failed',
                'provider_message_id' => $messageId,
                'network_id' => $networkId,
                'response_code' => $code,
                'response_description' => $description,
                'provider_response' => $raw,
                'shortcode' => $credentials['shortcode'],
                'context' => $context,
            ]);

            return [
                'ok' => $ok,
                'response' => $raw,
                'message_id' => $messageId,
                'response_code' => $code,
                'response_description' => $description,
                'sms_log_id' => $log->id,
            ];
        } catch (\Throwable $e) {
            $log = $this->createLog([
                'mobile' => $mobile,
                'message' => $message,
                'status' => 'failed',
                'shortcode' => $credentials['shortcode'],
                'context' => $context,
                'response_description' => $e->getMessage(),
                'provider_response' => Str::limit($e->getMessage(), 1000),
            ]);

            return [
                'ok' => false,
                'response' => $e->getMessage(),
                'message_id' => null,
                'response_code' => null,
                'response_description' => $e->getMessage(),
                'sms_log_id' => $log->id,
            ];
        }
    }

    /**
     * @return array{ok: bool, balance?: string|null, response?: string, response_code?: int|null}
     */
    public function getBalance(): array
    {
        if (! $this->settingsService->get('sms_enabled', false)) {
            return [
                'ok' => true,
                'balance' => 'N/A (SMS disabled / mock mode)',
                'response' => 'sms_disabled',
                'response_code' => 200,
            ];
        }

        $credentials = $this->credentials();
        if ($credentials === null) {
            return [
                'ok' => false,
                'balance' => null,
                'response' => 'SMS credentials incomplete',
                'response_code' => 4092,
            ];
        }

        try {
            $response = Http::timeout($credentials['timeout'])
                ->acceptJson()
                ->asJson()
                ->post($this->endpoint('getbalance'), [
                    'apikey' => $credentials['apikey'],
                    'partnerID' => $credentials['partnerID'],
                ]);

            $body = $response->json() ?? [];
            $code = $this->extractResponseCode($body) ?? ($response->successful() ? 200 : null);
            $balance = $this->extractBalance($body);
            $ok = $response->successful() && ($code === null || $code === 200) && $balance !== null;

            return [
                'ok' => $ok,
                'balance' => $balance,
                'response' => Str::limit($response->body(), 1000),
                'response_code' => $code,
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'balance' => null,
                'response' => $e->getMessage(),
                'response_code' => null,
            ];
        }
    }

    /**
     * @return array{ok: bool, response?: string, response_code?: int|null, data?: array<string, mixed>}
     */
    public function getDeliveryReport(string $messageId): array
    {
        $credentials = $this->credentials();
        if ($credentials === null) {
            return [
                'ok' => false,
                'response' => 'SMS credentials incomplete',
                'response_code' => 4092,
            ];
        }

        try {
            $response = Http::timeout($credentials['timeout'])
                ->acceptJson()
                ->asJson()
                ->post($this->endpoint('getdlr'), [
                    'apikey' => $credentials['apikey'],
                    'partnerID' => $credentials['partnerID'],
                    'messageID' => $messageId,
                ]);

            $body = $response->json() ?? [];
            $code = $this->extractResponseCode($body);
            $ok = $response->successful() && ($code === null || $code === 200);

            return [
                'ok' => $ok,
                'response' => Str::limit($response->body(), 1000),
                'response_code' => $code,
                'data' => is_array($body) ? $body : [],
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'response' => $e->getMessage(),
                'response_code' => null,
            ];
        }
    }

    public function isConfigured(): bool
    {
        return $this->credentials() !== null;
    }

    public function describeResponseCode(?int $code): string
    {
        if ($code === null) {
            return 'Unknown';
        }

        return self::RESPONSE_CODES[$code] ?? "Code {$code}";
    }

    /**
     * @return array{apikey: string, partnerID: string, shortcode: string, timeout: int}|null
     */
    protected function credentials(): ?array
    {
        $apikey = trim((string) $this->settingsService->get('sms_api_key', ''));
        $partnerId = trim((string) $this->settingsService->get('sms_partner_id', ''));
        $shortcode = trim((string) $this->settingsService->get('sms_sender_id', ''));

        if ($apikey === '' || $partnerId === '' || $shortcode === '') {
            return null;
        }

        return [
            'apikey' => $apikey,
            'partnerID' => $partnerId,
            'shortcode' => $shortcode,
            'timeout' => (int) $this->settingsService->get('sms_timeout', 15),
        ];
    }

    protected function endpoint(string $service): string
    {
        $base = rtrim((string) $this->settingsService->get('sms_base_url', self::BASE_URL), '/');

        return $base.'/'.ltrim($service, '/').'/';
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array<string, mixed>
     */
    protected function firstResponse(array $body): array
    {
        $responses = $body['responses'] ?? null;
        if (is_array($responses) && isset($responses[0]) && is_array($responses[0])) {
            return $responses[0];
        }

        return $body;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function extractResponseCode(array $payload): ?int
    {
        foreach (['respose-code', 'response-code', 'response_code', 'code'] as $key) {
            if (array_key_exists($key, $payload) && is_numeric($payload[$key])) {
                return (int) $payload[$key];
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function extractResponseDescription(array $payload, ?int $code): string
    {
        foreach (['response-description', 'response_description', 'description', 'message'] as $key) {
            if (! empty($payload[$key]) && is_scalar($payload[$key])) {
                return (string) $payload[$key];
            }
        }

        return $this->describeResponseCode($code);
    }

    /**
     * @param  array<string, mixed>  $body
     */
    protected function extractBalance(array $body): ?string
    {
        foreach (['balance', 'credit', 'credits', 'account_balance'] as $key) {
            if (array_key_exists($key, $body) && $body[$key] !== null && $body[$key] !== '') {
                return is_scalar($body[$key]) ? (string) $body[$key] : null;
            }
        }

        $first = $this->firstResponse($body);
        foreach (['balance', 'credit', 'credits'] as $key) {
            if (array_key_exists($key, $first) && $first[$key] !== null && $first[$key] !== '') {
                return is_scalar($first[$key]) ? (string) $first[$key] : null;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function createLog(array $attributes): SmsLog
    {
        return SmsLog::create($attributes);
    }
}

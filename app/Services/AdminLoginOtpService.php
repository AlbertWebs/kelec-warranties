<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AdminLoginOtpService
{
    public const SESSION_KEY = 'login_otp';

    public const CODE_TTL_SECONDS = 600;

    public const MAX_VERIFY_ATTEMPTS = 5;

    public function __construct(
        protected SmsService $smsService,
        protected PhoneNumberService $phoneNumberService,
        protected SettingsService $settingsService,
    ) {}

    /**
     * @return array{masked_mobile: string}
     */
    public function startChallenge(User $user, bool $remember = false): array
    {
        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'This account is inactive.',
            ]);
        }

        $mobile = $user->mobile_normalized ?: $this->phoneNumberService->normalize($user->mobile_number);
        if (! $mobile) {
            throw ValidationException::withMessages([
                'email' => 'Your account has no mobile number for SMS verification. Ask a super admin to add one.',
            ]);
        }

        $code = (string) random_int(100000, 999999);
        Cache::put($this->cacheKey($user->id), Hash::make($code), self::CODE_TTL_SECONDS);

        session([
            self::SESSION_KEY => [
                'user_id' => $user->id,
                'remember' => $remember,
                'sent_at' => now()->timestamp,
                'attempts' => 0,
            ],
        ]);

        $message = "K-Elec: Your staff login code is {$code}. Valid for 10 minutes. Do not share this code.";
        $result = $this->smsService->send($mobile, $message, 'admin_login_otp');

        if (! ($result['ok'] ?? false) && $this->settingsService->get('sms_enabled', false)) {
            $this->clearChallenge();
            Cache::forget($this->cacheKey($user->id));

            throw ValidationException::withMessages([
                'email' => 'Unable to send login SMS. Please try again or contact support.',
            ]);
        }

        return ['masked_mobile' => $this->maskMobile($mobile)];
    }

    public function pendingUser(): ?User
    {
        $payload = session(self::SESSION_KEY);
        if (! is_array($payload) || empty($payload['user_id'])) {
            return null;
        }

        return User::query()->find($payload['user_id']);
    }

    public function maskedMobileForPending(): ?string
    {
        $user = $this->pendingUser();
        if (! $user) {
            return null;
        }

        $mobile = $user->mobile_normalized ?: $this->phoneNumberService->normalize($user->mobile_number);

        return $mobile ? $this->maskMobile($mobile) : null;
    }

    /**
     * @return array{user: User, remember: bool}
     */
    public function verify(string $code): array
    {
        $user = $this->pendingUser();
        if (! $user) {
            throw ValidationException::withMessages([
                'otp' => 'Your login session expired. Please sign in again.',
            ]);
        }

        $payload = session(self::SESSION_KEY, []);
        $remember = (bool) ($payload['remember'] ?? false);
        $attempts = (int) ($payload['attempts'] ?? 0);
        if ($attempts >= self::MAX_VERIFY_ATTEMPTS) {
            $this->clearChallenge();
            Cache::forget($this->cacheKey($user->id));

            throw ValidationException::withMessages([
                'otp' => 'Too many invalid codes. Please sign in again.',
            ]);
        }

        $hash = Cache::get($this->cacheKey($user->id));
        if (! is_string($hash) || ! Hash::check(trim($code), $hash)) {
            $payload['attempts'] = $attempts + 1;
            session([self::SESSION_KEY => $payload]);

            throw ValidationException::withMessages([
                'otp' => 'Invalid or expired verification code.',
            ]);
        }

        if (! $user->is_active) {
            $this->clearChallenge();
            Cache::forget($this->cacheKey($user->id));

            throw ValidationException::withMessages([
                'otp' => 'This account is inactive.',
            ]);
        }

        Cache::forget($this->cacheKey($user->id));
        $this->clearChallenge();

        return ['user' => $user, 'remember' => $remember];
    }

    public function rememberPending(): bool
    {
        return (bool) data_get(session(self::SESSION_KEY), 'remember', false);
    }

    public function resend(): array
    {
        $user = $this->pendingUser();
        if (! $user) {
            throw ValidationException::withMessages([
                'otp' => 'Your login session expired. Please sign in again.',
            ]);
        }

        $key = 'login-otp-resend:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'otp' => "Please wait {$seconds} seconds before requesting another code.",
            ]);
        }

        RateLimiter::hit($key, 60);

        return $this->startChallenge($user, $this->rememberPending());
    }

    public function clearChallenge(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public function maskMobile(string $mobile): string
    {
        $digits = preg_replace('/\D+/', '', $mobile) ?? $mobile;
        if (strlen($digits) < 6) {
            return '***';
        }

        return substr($digits, 0, 3).str_repeat('*', max(strlen($digits) - 6, 3)).substr($digits, -3);
    }

    protected function cacheKey(int $userId): string
    {
        return 'admin_login_otp:'.$userId;
    }
}

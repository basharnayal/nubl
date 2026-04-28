<?php

namespace App\Services;

use App\Models\User;
use App\Support\PhoneHelper;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * OTP generation, storage (cache), sending, and verification.
 * Rate limiting: 6 resends per hour per user.
 */
class OtpService
{
    public const OTP_LENGTH = 6;

    public const OTP_TTL_MINUTES = 5;

    public const RESEND_LIMIT = 6;

    public const RESEND_WINDOW_MINUTES = 60;

    public function __construct(
        private SmsService $smsService
    ) {}

    /**
     * Generate a 6-digit OTP.
     */
    public function generate(): string
    {
        $min = (int) str_pad('1', self::OTP_LENGTH, '0');
        $max = (int) str_repeat('9', self::OTP_LENGTH);

        return (string) random_int($min, $max);
    }

    /**
     * Cache key for OTP storage.
     */
    protected function otpKey(int $userId): string
    {
        return 'otp:user:'.$userId;
    }

    /**
     * Cache key for resend rate limiting.
     */
    protected function resendKey(int $userId): string
    {
        return 'otp:resend:'.$userId;
    }

    /**
     * Get phone number for user (User.phone_number or ProviderProfile.phone_number).
     */
    protected function getPhoneForUser(User $user): ?string
    {
        if (! empty($user->phone_number)) {
            return $user->phone_number;
        }
        $profile = $user->providerProfile;

        return $profile?->phone_number;
    }

    /**
     * Send OTP to user's phone. Stores OTP in cache. Respects resend limit.
     *
     * @return array{success: bool, message: string}
     */
    public function sendOtp(User $user): array
    {
        $phone = $this->getPhoneForUser($user);
        if (empty($phone)) {
            Log::warning('OtpService: No phone number for user', ['user_id' => $user->id]);

            return ['success' => false, 'message' => __('No phone number associated with this account.')];
        }

        $resendKey = $this->resendKey($user->id);
        $count = (int) Cache::get($resendKey, 0);
        if ($count >= self::RESEND_LIMIT) {
            return [
                'success' => false,
                'message' => __('Too many attempts. Please try again later.'),
            ];
        }

        $otp = $this->generate();
        $cacheKey = $this->otpKey($user->id);
        Cache::put($cacheKey, $otp, now()->addMinutes(self::OTP_TTL_MINUTES));
        Cache::put($resendKey, $count + 1, now()->addMinutes(self::RESEND_WINDOW_MINUTES));

        $body = __('Your NUBL verification code is: :code. Valid for :minutes minutes.', [
            'code' => $otp,
            'minutes' => self::OTP_TTL_MINUTES,
        ]);

        $sent = $this->smsService->send($phone, $body);

        if (! $sent) {
            Log::warning('OtpService: SMS send failed; OTP not logged for security', [
                'user_id' => $user->id,
                'phone' => PhoneHelper::maskForLog($phone),
            ]);
        }

        return [
            'success' => $sent,
            'message' => $sent
                ? __('Verification code sent to your phone.')
                : __('Failed to send verification code. Please try again.'),
        ];
    }

    /**
     * Cache key for login OTP (by phone number).
     */
    protected function loginOtpKey(string $phone): string
    {
        return 'otp:login:'.$phone;
    }

    /**
     * Resend key for login OTP rate limiting.
     */
    protected function loginResendKey(string $phone): string
    {
        return 'otp:login:resend:'.$phone;
    }

    /**
     * Send OTP for login (user not authenticated). User must exist by phone.
     *
     * @return array{success: bool, message: string}
     */
    public function sendOtpForLogin(string $phone): array
    {
        $normalized = \App\Support\PhoneHelper::normalize($phone);
        if (! \App\Support\PhoneHelper::isValid($phone)) {
            return ['success' => false, 'message' => __('Invalid phone number.')];
        }

        $resendKey = $this->loginResendKey($normalized);
        $count = (int) Cache::get($resendKey, 0);
        if ($count >= self::RESEND_LIMIT) {
            return [
                'success' => false,
                'message' => __('Too many attempts. Please try again later.'),
            ];
        }

        $otp = $this->generate();
        $cacheKey = $this->loginOtpKey($normalized);
        Cache::put($cacheKey, $otp, now()->addMinutes(self::OTP_TTL_MINUTES));
        Cache::put($resendKey, $count + 1, now()->addMinutes(self::RESEND_WINDOW_MINUTES));

        $body = __('Your NUBL verification code is: :code. Valid for :minutes minutes.', [
            'code' => $otp,
            'minutes' => self::OTP_TTL_MINUTES,
        ]);

        $sent = $this->smsService->send($normalized, $body);

        if (! $sent) {
            Log::warning('OtpService: login SMS send failed; OTP not logged for security', [
                'phone' => PhoneHelper::maskForLog($normalized),
            ]);
        }

        return [
            'success' => $sent,
            'message' => $sent
                ? __('Verification code sent to your phone.')
                : __('Failed to send verification code. Please try again.'),
        ];
    }

    /**
     * Verify OTP for login. Returns User if valid, null otherwise.
     */
    public function verifyOtpForLogin(string $phone, string $code): ?User
    {
        $normalized = \App\Support\PhoneHelper::normalize($phone);
        $code = preg_replace('/\D/', '', $code);
        if (strlen($code) !== self::OTP_LENGTH) {
            return null;
        }

        $cacheKey = $this->loginOtpKey($normalized);
        $stored = Cache::get($cacheKey);

        if ($stored === null || $stored !== $code) {
            return null;
        }

        Cache::forget($cacheKey);

        return User::findByPhone($normalized);
    }

    /**
     * Verify OTP for user.
     */
    public function verifyOtp(User $user, string $code): bool
    {
        $code = preg_replace('/\D/', '', $code);
        if (strlen($code) !== self::OTP_LENGTH) {
            return false;
        }

        $cacheKey = $this->otpKey($user->id);
        $stored = Cache::get($cacheKey);

        if ($stored === null) {
            return false;
        }

        if ($stored !== $code) {
            return false;
        }

        Cache::forget($cacheKey);

        return true;
    }
}

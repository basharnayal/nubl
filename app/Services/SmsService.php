<?php

namespace App\Services;

use App\Helpers\PhoneHelper;
use Illuminate\Support\Facades\Log;

/**
 * SMS service wrapping Taqnyat API.
 * Handles sending SMS with proper error handling and logging.
 * Taqnyat expects recipients as digits: 966XXXXXXXXX.
 */
class SmsService
{
    public function __construct(
        private ?string $bearer = null,
        private ?string $sender = null
    ) {
        $this->bearer = $bearer ?? config('services.taqnyat.bearer');
        $this->sender = $sender ?? config('services.taqnyat.sender', 'NUBL');
    }

    /**
     * Send SMS via Taqnyat.
     *
     * @return bool True if sent successfully
     */
    public function send(string $to, string $body): bool
    {
        if (empty($this->bearer)) {
            Log::warning('SmsService: TAQNYAT_BEARER_TOKEN not configured. Skipping SMS send.');
            Log::info('SmsService [no-token]: Would send to ' . $to . ' | Body: ' . $body);
            return false;
        }

        try {
            $taqnyat = new \TaqnyatSms($this->bearer);
            $recipient = PhoneHelper::normalize($to);
            $recipients = [$recipient];
            $result = $taqnyat->sendMsg($body, $recipients, $this->sender, '');

            if (is_string($result) && (str_contains($result, 'error') || str_contains($result, 'Error'))) {
                Log::error('SmsService: Taqnyat error', ['response' => $result, 'to' => $to]);
                return false;
            }

            Log::info('SmsService: SMS sent successfully', ['to' => $to]);
            return true;
        } catch (\Throwable $e) {
            Log::error('SmsService: Failed to send SMS', [
                'to' => $to,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return false;
        }
    }
}

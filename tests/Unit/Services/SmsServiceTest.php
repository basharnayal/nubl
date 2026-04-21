<?php

namespace Tests\Unit\Services;

use App\Services\SmsService;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SmsServiceTest extends TestCase
{
    #[Test]
    public function send_returns_false_when_bearer_token_is_missing(): void
    {
        Log::spy();

        $service = new SmsService('', 'NUBL');

        $this->assertFalse($service->send('0501234567', 'OTP 123456'));
        Log::shouldHaveReceived('warning')
            ->once()
            ->with('SmsService: TAQNYAT_BEARER_TOKEN not configured. Skipping SMS send.');
    }

    #[Test]
    public function send_returns_false_when_gateway_reports_an_error_response(): void
    {
        Log::spy();

        $gateway = new class {
            public function sendMsg(string $body, array $recipients, string $sender, string $scheduled): string
            {
                TestCase::assertSame('OTP 123456', $body);
                TestCase::assertSame(['966501234567'], $recipients);
                TestCase::assertSame('NUBL', $sender);
                TestCase::assertSame('', $scheduled);

                return 'Error: invalid sender';
            }
        };

        $service = new SmsService('token', 'NUBL', fn () => $gateway);

        $this->assertFalse($service->send('0501234567', 'OTP 123456'));

        Log::shouldHaveReceived('error')
            ->once()
            ->with('SmsService: Taqnyat error', [
                'response' => 'Error: invalid sender',
                'to' => '966*******67',
            ]);
    }

    #[Test]
    public function send_returns_false_and_logs_when_gateway_throws(): void
    {
        Log::spy();

        $gateway = new class {
            public function sendMsg(): never
            {
                throw new \RuntimeException('gateway unavailable');
            }
        };

        $service = new SmsService('token', 'NUBL', fn () => $gateway);

        $this->assertFalse($service->send('0501234567', 'OTP 123456'));

        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(function (string $message, array $context): bool {
                return $message === 'SmsService: Failed to send SMS'
                    && $context['to'] === '050*****67'
                    && $context['error'] === 'gateway unavailable'
                    && is_string($context['trace']);
            });
    }
}

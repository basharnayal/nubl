<?php

namespace Tests\Unit\Services;

use App\Services\MyFatoorahService;
use MyFatoorah\Library\PaymentMyfatoorahApiV2;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use stdClass;
use Tests\TestCase;

class MyFatoorahServiceTest extends TestCase
{
    #[Test]
    public function create_invoice_returns_normalized_payload_and_passes_customer_email_when_provided(): void
    {
        $fakeClient = new class extends PaymentMyfatoorahApiV2
        {
            public array $capturedPostFields = [];

            public function __construct() {}

            public function getInvoiceURL($postFields, $arg2 = 0, $arg3 = null, $arg4 = null, $arg5 = 'LNK')
            {
                $this->capturedPostFields = $postFields;

                return [
                    'invoiceId' => 'INV-1001',
                    'invoiceURL' => 'https://pay.example.test/invoice/INV-1001',
                    'meta' => 'ok',
                ];
            }
        };

        $service = $this->serviceWithFakeClient($fakeClient);

        $result = $service->createInvoice(
            125.50,
            'REF-1',
            'https://app.test/callback',
            'https://app.test/error',
            'donor@example.test',
            'Donor User'
        );

        $this->assertSame('INV-1001', $result['invoice_id']);
        $this->assertSame('https://pay.example.test/invoice/INV-1001', $result['payment_url']);
        $this->assertSame('donor@example.test', $fakeClient->capturedPostFields['CustomerEmail']);
        $this->assertSame('REF-1', $fakeClient->capturedPostFields['CustomerReference']);
    }

    #[Test]
    public function create_invoice_omits_customer_email_when_not_provided(): void
    {
        $fakeClient = new class extends PaymentMyfatoorahApiV2
        {
            public array $capturedPostFields = [];

            public function __construct() {}

            public function getInvoiceURL($postFields, $arg2 = 0, $arg3 = null, $arg4 = null, $arg5 = 'LNK')
            {
                $this->capturedPostFields = $postFields;

                return ['invoiceId' => 77, 'invoiceURL' => 'https://pay.example.test/77'];
            }
        };

        $service = $this->serviceWithFakeClient($fakeClient);

        $service->createInvoice(
            50.00,
            'REF-2',
            'https://app.test/callback',
            'https://app.test/error'
        );

        $this->assertArrayNotHasKey('CustomerEmail', $fakeClient->capturedPostFields);
    }

    #[Test]
    public function get_payment_status_returns_scalar_string_status_and_invoice_id(): void
    {
        $fakeClient = new class extends PaymentMyfatoorahApiV2
        {
            public mixed $response;

            public function __construct() {}

            public function getPaymentStatus($keyId, $keyType = 'PaymentId', $arg3 = null, $arg4 = null, $arg5 = null)
            {
                return $this->response;
            }
        };
        $fakeClient->response = (object) [
            'InvoiceStatus' => 5,
            'InvoiceId' => 'INV-55',
            'AnyField' => 'value',
        ];

        $service = $this->serviceWithFakeClient($fakeClient);

        $result = $service->getPaymentStatus('PAY-55');

        $this->assertSame('5', $result['status']);
        $this->assertSame('INV-55', $result['invoice_id']);
        $this->assertIsArray($result['raw_response']);
        $this->assertSame(5, $result['raw_response']['InvoiceStatus']);
    }

    #[Test]
    public function get_payment_status_returns_unknown_when_status_is_non_scalar(): void
    {
        $fakeClient = new class extends PaymentMyfatoorahApiV2
        {
            public function __construct() {}

            public function getPaymentStatus($keyId, $keyType = 'PaymentId', $arg3 = null, $arg4 = null, $arg5 = null)
            {
                return (object) [
                    'InvoiceStatus' => new stdClass,
                    'InvoiceId' => 9001,
                ];
            }
        };

        $service = $this->serviceWithFakeClient($fakeClient);
        $result = $service->getPaymentStatus('PAY-9001');

        $this->assertSame('Unknown', $result['status']);
        $this->assertSame(9001, $result['invoice_id']);
    }

    #[Test]
    public function get_payment_status_throws_when_gateway_response_is_invalid(): void
    {
        $fakeClient = new class extends PaymentMyfatoorahApiV2
        {
            public function __construct() {}

            public function getPaymentStatus($keyId, $keyType = 'PaymentId', $arg3 = null, $arg4 = null, $arg5 = null)
            {
                return null;
            }
        };

        $service = $this->serviceWithFakeClient($fakeClient);

        $this->expectException(RuntimeException::class);
        $service->getPaymentStatus('PAY-NULL');
    }

    private function serviceWithFakeClient(PaymentMyfatoorahApiV2 $fakeClient): MyFatoorahService
    {
        config([
            'services.myfatoorah.api_key' => 'test_api_key',
            'services.myfatoorah.country_code' => 'SAU',
            'services.myfatoorah.is_test' => true,
        ]);

        $service = new MyFatoorahService;

        $reflection = new \ReflectionProperty(MyFatoorahService::class, 'client');
        $reflection->setAccessible(true);
        $reflection->setValue($service, $fakeClient);

        return $service;
    }
}

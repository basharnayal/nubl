<?php

namespace App\Services;

use MyFatoorah\Library\PaymentMyfatoorahApiV2;

class MyFatoorahService
{
    private PaymentMyfatoorahApiV2 $client;

    public function __construct()
    {
        $config = config('services.myfatoorah');
        $this->client = new PaymentMyfatoorahApiV2(
            $config['api_key'],
            $config['country_code'],
            $config['is_test']
        );
    }

    /**
     * Create an invoice and get the payment URL.
     *
     * @return array{invoice_id: int|string, payment_url: string, raw_response: array}
     */
    public function createInvoice(
        float $amount,
        string $customerReference,
        string $callbackUrl,
        string $errorUrl,
        ?string $customerEmail = null,
        ?string $customerName = null
    ): array {
        $postFields = [
            'InvoiceValue' => (string) $amount,
            'DisplayCurrencyIso' => 'SAR',
            'CustomerName' => $customerName ?? 'Donor',
            'NotificationOption' => 'LNK',
            'CallBackUrl' => $callbackUrl,
            'ErrorUrl' => $errorUrl,
            'CustomerReference' => $customerReference,
        ];

        if ($customerEmail) {
            $postFields['CustomerEmail'] = $customerEmail;
        }

        $result = $this->client->getInvoiceURL($postFields, 0, $customerReference, null, 'LNK');

        return [
            'invoice_id' => $result['invoiceId'],
            'payment_url' => $result['invoiceURL'],
            'raw_response' => $result,
        ];
    }

    /**
     * Get payment status from MyFatoorah.
     *
     * @return array{status: string, invoice_id: int|string, raw_response: array}
     */
    public function getPaymentStatus(string $keyId, string $keyType = 'PaymentId'): array
    {
        $data = $this->client->getPaymentStatus($keyId, $keyType, null, null, null);

        if ($data === null || ! is_object($data)) {
            throw new \RuntimeException('MyFatoorah getPaymentStatus returned an empty or invalid response.');
        }

        $rawArray = json_decode(json_encode($data), true);
        if (! is_array($rawArray)) {
            $rawArray = [];
        }

        $status = $data->InvoiceStatus ?? 'Unknown';
        if (! is_string($status)) {
            $status = is_scalar($status) ? (string) $status : 'Unknown';
        }

        return [
            'status' => $status,
            'invoice_id' => $data->InvoiceId ?? null,
            'raw_response' => $rawArray,
        ];
    }
}

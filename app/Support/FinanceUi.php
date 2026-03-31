<?php

namespace App\Support;

class FinanceUi
{
    /**
     * Human label for JSON / audit property keys (invoiceId, payment_id, …).
     */
    public static function fieldLabel(string $key): string
    {
        $k = 'finance.fields.'.$key;
        $trans = __($k);

        return $trans !== $k ? $trans : self::humanizeKey($key);
    }

    /**
     * Short title for Spatie activity `description` (e.g. payment.succeeded).
     */
    public static function auditTitle(string $description): string
    {
        $slug = str_replace(['.', '-'], '_', $description);
        $k = 'finance.audit.'.$slug;
        $trans = __($k);

        return $trans !== $k ? $trans : $description;
    }

    private static function humanizeKey(string $key): string
    {
        $key = str_replace('_', ' ', $key);

        return (string) str($key)->title();
    }
}

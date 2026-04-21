<?php

namespace App\Support;

/**
 * Saudi phone number normalization for Taqnyat and DB storage.
 * Output format: 966XXXXXXXXX (E.164 without +, digits only).
 * Matches Taqnyat API expected format.
 */
class PhoneHelper
{
    /**
     * Normalize Saudi phone to Taqnyat/DB format: 966XXXXXXXXX.
     *
     * Accepts: 05XXXXXXXX, 5XXXXXXXX, +9665..., 9665..., 009665...
     */
    public static function normalize(string $phone): string
    {
        $cleaned = self::nationalMobileDigits($phone);

        if (strlen($cleaned) === 9 && preg_match('/^[125][0-9]{8}$/', $cleaned)) {
            return '966' . $cleaned;
        }

        return '966' . $cleaned;
    }

    /**
     * National mobile digits only (9), after +966 / 966 / 00966 / leading-zero handling.
     * Same parsing path as validation; does not guarantee a valid Saudi mobile.
     */
    public static function nationalMobileDigits(string $phone): string
    {
        $cleaned = preg_replace('/[^0-9+]/', '', (string) $phone);

        if (str_starts_with($cleaned, '+966')) {
            $cleaned = substr($cleaned, 4);
        } elseif (str_starts_with($cleaned, '00966')) {
            $cleaned = substr($cleaned, 5);
        } elseif (str_starts_with($cleaned, '966')) {
            $cleaned = substr($cleaned, 3);
        }

        return ltrim($cleaned, '0');
    }

    /**
     * Check if string is a valid Saudi phone number.
     */
    public static function isValid(string $phone): bool
    {
        $cleaned = self::nationalMobileDigits($phone);

        return strlen($cleaned) === 9 && preg_match('/^[125][0-9]{8}$/', $cleaned);
    }

    /**
     * Format for input field (05XXXXXXXX) - user-friendly for editing.
     */
    public static function formatForInput(string $phone): string
    {
        $normalized = self::normalize($phone);
        if (strlen($normalized) === 12 && str_starts_with($normalized, '966')) {
            return '0' . substr($normalized, 3);
        }
        return $phone;
    }

    /**
     * Mask digits for logging (PII). Keeps a short prefix + last 2 digits for support correlation.
     * Example: 966501234567 → 966*******67
     */
    public static function maskForLog(?string $phone): string
    {
        if ($phone === null || $phone === '') {
            return '';
        }

        $digits = preg_replace('/\D/', '', (string) $phone);
        $len = strlen($digits);

        if ($len <= 4) {
            return str_repeat('*', $len);
        }

        $headLen = min(3, $len - 3);
        $tailLen = 2;
        $middleLen = $len - $headLen - $tailLen;

        if ($middleLen < 1) {
            return str_repeat('*', $len);
        }

        return substr($digits, 0, $headLen).str_repeat('*', $middleLen).substr($digits, -$tailLen);
    }

    /**
     * Format for display (e.g. +966 50 006 1559).
     */
    public static function formatForDisplay(string $phone): string
    {
        $normalized = self::normalize($phone);
        if (strlen($normalized) === 12) {
            return '+' . substr($normalized, 0, 3) . ' ' . substr($normalized, 3, 2) . ' ' . substr($normalized, 5, 3) . ' ' . substr($normalized, 8);
        }
        return $phone;
    }
}

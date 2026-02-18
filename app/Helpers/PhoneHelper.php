<?php

namespace App\Helpers;

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
        $cleaned = preg_replace('/[^0-9+]/', '', (string) $phone);

        if (str_starts_with($cleaned, '+966')) {
            $cleaned = substr($cleaned, 4);
        } elseif (str_starts_with($cleaned, '00966')) {
            $cleaned = substr($cleaned, 5);
        } elseif (str_starts_with($cleaned, '966')) {
            $cleaned = substr($cleaned, 3);
        }

        $cleaned = ltrim($cleaned, '0');

        if (strlen($cleaned) === 9 && preg_match('/^[125][0-9]{8}$/', $cleaned)) {
            return '966' . $cleaned;
        }

        return '966' . $cleaned;
    }

    /**
     * Check if string is a valid Saudi phone number.
     */
    public static function isValid(string $phone): bool
    {
        $cleaned = preg_replace('/[^0-9+]/', '', $phone);

        if (str_starts_with($cleaned, '+966')) {
            $cleaned = substr($cleaned, 4);
        } elseif (str_starts_with($cleaned, '00966')) {
            $cleaned = substr($cleaned, 5);
        } elseif (str_starts_with($cleaned, '966')) {
            $cleaned = substr($cleaned, 3);
        }

        $cleaned = ltrim($cleaned, '0');

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

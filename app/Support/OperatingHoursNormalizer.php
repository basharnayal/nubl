<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Normalizes provider operating_hours[] POST input to the JSON shape stored on ProviderOperatingInfo.
 */
final class OperatingHoursNormalizer
{
    /**
     * @return array<string, array{open?: string, close?: string, closed: bool}>
     */
    public static function fromRequest(Request $request): array
    {
        $weekdays = array_keys(config('provider.weekdays'));
        $oh = $request->input('operating_hours', []);
        $operatingHours = [];

        foreach ($weekdays as $day) {
            $dayData = $oh[$day] ?? [];
            $closed = filter_var($dayData['closed'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($closed) {
                $operatingHours[$day] = ['closed' => true];
            } else {
                $open = trim((string) ($dayData['open'] ?? ''));
                $close = trim((string) ($dayData['close'] ?? ''));
                if ($open === '' || $close === '') {
                    throw ValidationException::withMessages([
                        "operating_hours.{$day}" => [__('Set opening and closing time, or mark as closed.')],
                    ]);
                }
                $operatingHours[$day] = ['open' => $open, 'close' => $close, 'closed' => false];
            }
        }

        return $operatingHours;
    }
}

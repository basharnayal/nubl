<?php

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class UpdateProviderProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->hasRole('provider');
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $weekdays = array_keys(config('provider.weekdays'));

        return [
            'daily_capacity' => ['required', 'integer', 'min:1', 'max:10000'],
            'service_type' => ['required', 'array', 'min:1'],
            'service_type.*' => ['string', 'in:'.implode(',', config('provider.service_types'))],
            'estimated_preparation_order_time' => ['required', 'string', 'max:100'],
            'adoption_support' => ['required', 'string', 'in:yes,partially,no'],
            'pickup_notes' => ['nullable', 'string', 'max:2000'],
            'operating_hours' => ['required', 'array'],
            ...collect($weekdays)->mapWithKeys(fn ($d) => ["operating_hours.{$d}" => ['required', 'array']])->all(),
        ];
    }

    /**
     * @return array<string, array{open?: string, close?: string, closed: bool}|array{closed: bool}>
     */
    public function buildOperatingHours(): array
    {
        $weekdays = array_keys(config('provider.weekdays'));
        $oh = $this->input('operating_hours', []);
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

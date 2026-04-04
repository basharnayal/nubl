<?php

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexProviderRequestsRequest extends FormRequest
{
    /** Status values allowed in the incoming-requests filter (matches provider UI). */
    public const FILTER_STATUSES = [
        'REQUESTED',
        'APPROVED',
        'ADMIN_PENDING',
        'ADMIN_APPROVED',
        'REDEEMABLE',
        'FULFILLED',
        'REJECTED',
        'CANCELLED',
        'ADMIN_REJECTED',
    ];

    public function authorize(): bool
    {
        return $this->user()->hasRole('provider');
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'status' => ['nullable', 'string', Rule::in(self::FILTER_STATUSES)],
            'needs_proof' => ['nullable', 'in:1'],
            'q' => ['nullable', 'string', 'max:40'],
            'per_page' => ['nullable', 'integer', Rule::in([15, 25, 50])],
        ];
    }
}

<?php

namespace App\Rules;

use App\Support\PhoneHelper;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Validates that the (normalized) phone number is unique in users table.
 */
class SaudiPhoneUnique implements ValidationRule
{
    public function __construct(
        protected ?int $ignoreUserId = null
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(__('The :attribute must be a valid Saudi phone number.'));
            return;
        }

        $normalized = PhoneHelper::normalize($value);

        $query = User::where('phone_number', $normalized)->whereNotNull('phone_number');
        if ($this->ignoreUserId) {
            $query->where('id', '!=', $this->ignoreUserId);
        }
        if ($query->exists()) {
            $fail(__('This phone number is already registered.'));
        }
    }
}

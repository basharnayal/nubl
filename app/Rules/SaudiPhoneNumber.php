<?php

namespace App\Rules;

use App\Helpers\PhoneHelper;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SaudiPhoneNumber implements ValidationRule
{
    /**
     * Saudi phone format: 9 digits starting with 5, 1, or 2.
     * Accepts: 05XXXXXXXX, 5XXXXXXXX, +9665..., 9665..., 009665...
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(__('The :attribute must be a valid Saudi phone number.'));
            return;
        }

        if (! PhoneHelper::isValid($value)) {
            $fail(__('The :attribute must be a valid Saudi phone number (e.g. 05XXXXXXXX).'));
        }
    }
}

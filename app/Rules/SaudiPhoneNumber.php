<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SaudiPhoneNumber implements ValidationRule
{
    /**
     * Saudi phone format: (009665|9665|\+9665|05|5)(5|0|3|6|4|9|1|8|7)([0-9]{7})
     * Validates international (+966/00966), standard (966), or local (05/5) format.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(__('The :attribute must be a valid Saudi phone number.'));
            return;
        }

        $normalized = preg_replace('/\s+/', '', $value);
        $pattern = '/^(009665|9665|\+9665|05|5)(5|0|3|6|4|9|1|8|7)([0-9]{7})$/';

        if (! preg_match($pattern, $normalized)) {
            $fail(__('The :attribute must be a valid Saudi phone number (e.g. 05XXXXXXXX).'));
        }
    }
}

<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Base64Image implements ValidationRule
{
    /**
     * Validate that the value is a valid base64-encoded image.
     * Rejects regular file uploads - only base64 from camera capture is accepted.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            $fail(__('The identity photo must be captured using your device camera.'));

            return;
        }

        // Must look like base64 data URL
        if (! preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,/i', $value)) {
            $fail(__('The identity photo must be captured using your device camera.'));

            return;
        }

        $base64 = preg_replace('/^data:image\/(jpeg|jpg|png|webp);base64,/', '', $value);
        if (empty($base64)) {
            $fail(__('The identity photo appears to be empty.'));

            return;
        }

        $decoded = base64_decode($base64, true);
        if ($decoded === false) {
            $fail(__('The identity photo format is invalid.'));

            return;
        }

        // Basic size check (e.g. max 5MB)
        if (strlen($decoded) > 5 * 1024 * 1024) {
            $fail(__('The identity photo is too large.'));

            return;
        }
    }
}

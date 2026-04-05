<?php

namespace App\Support;

use App\Models\ProviderProfile;
use Illuminate\Support\Str;

/**
 * Localized labels for provider profile fields (JSON keys provider.*), same pattern as {@see FinanceUi}.
 */
class ProviderDisplay
{
    public static function businessTitle(?ProviderProfile $profile, string $fallbackName): string
    {
        if (app()->getLocale() === 'ar' && $profile?->business_name_ar) {
            return $profile->business_name_ar;
        }

        return $profile?->business_name_en ?? $profile?->business_name_ar ?? $fallbackName;
    }

    public static function initials(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '??';
        }

        return Str::upper(Str::substr($name, 0, 2));
    }

    public static function businessCategoryLabel(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $k = 'provider.business_category.'.$slug;
        $t = __($k);

        return $t !== $k ? $t : ucfirst(str_replace('_', ' ', $slug));
    }

    /**
     * @param  array<int, mixed>|null  $categories
     */
    public static function businessCategoryLine(?array $categories): ?string
    {
        if (! $categories) {
            return null;
        }

        return implode(', ', array_map(function ($c) {
            $slug = is_string($c) ? $c : (string) ($c['name'] ?? $c);

            return self::businessCategoryLabel($slug);
        }, $categories));
    }

    public static function serviceTypeLabel(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $k = 'provider.service_type.'.$slug;
        $t = __($k);

        return $t !== $k ? $t : ucfirst(str_replace('_', ' ', $slug));
    }

    /**
     * @param  array<int, string>  $types
     */
    public static function serviceTypeLine(array $types): string
    {
        return implode(', ', array_map(fn ($s) => self::serviceTypeLabel((string) $s), $types));
    }

    public static function cityLabel(?string $city): string
    {
        if ($city === null || $city === '') {
            return __('Unknown City');
        }

        return self::translatedCity($city);
    }

    /**
     * City for combined address lines (no "Unknown" placeholder when empty).
     */
    public static function translatedCity(string $city): string
    {
        $ck = 'provider.city.'.str_replace([' ', '-'], '_', strtolower(trim($city)));
        $ct = __($ck);

        return $ct !== $ck ? $ct : $city;
    }

    /**
     * Recipient-facing location: stored location, or translated city + region.
     */
    public static function locationLine(ProviderProfile $profile): string
    {
        if (filled($profile->location)) {
            return $profile->location;
        }

        return implode(', ', array_filter([
            filled($profile->city) ? self::translatedCity($profile->city) : null,
            $profile->region ?? null,
        ]));
    }
}

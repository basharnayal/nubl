<?php

declare(strict_types=1);

namespace App\Support;

final class Nationality
{
    /**
     * English stored value + Arabic for display/search (e.g. on registration forms).
     */
    public static function bilingualLabel(string $englishName): string
    {
        $ar = config('nationality_ar')[$englishName] ?? null;

        return ($ar !== null && $ar !== '')
            ? $englishName.' — '.$ar
            : $englishName;
    }
}

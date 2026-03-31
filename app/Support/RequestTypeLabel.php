<?php

namespace App\Support;

use App\Models\Request as RequestModel;

/**
 * Human-readable request line item label (category / item) — no recipient PII.
 */
final class RequestTypeLabel
{
    public static function forRequest(RequestModel $request): string
    {
        $firstItem = $request->items->first();
        if (! $firstItem?->menuItem) {
            return __('Request');
        }

        $menuItem = $firstItem->menuItem;

        $categoryName = $menuItem->menuItemCategory?->name;
        if ($categoryName && ! preg_match('/^\d+$/', (string) $categoryName)) {
            return $categoryName;
        }

        $itemName = $menuItem->name;
        if ($itemName && ! preg_match('/^\d+$/', (string) $itemName)) {
            return $itemName;
        }

        $legacyCategory = $menuItem->category;
        if ($legacyCategory && ! preg_match('/^\d+$/', (string) $legacyCategory)) {
            return match (strtolower((string) $legacyCategory)) {
                'meal', 'meals' => __('Meals'),
                'bakery' => __('Bakery'),
                'basket' => __('Food basket'),
                'catering' => __('Catering'),
                'grocery' => __('Grocery'),
                default => ucfirst((string) $legacyCategory),
            };
        }

        return __('Request');
    }
}

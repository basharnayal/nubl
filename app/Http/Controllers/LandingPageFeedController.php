<?php

namespace App\Http\Controllers;

use App\Services\LandingPageStatsService;
use Illuminate\Http\JsonResponse;

class LandingPageFeedController extends Controller
{
    /**
     * Uncached live feed for the home hero ticker; respects session locale.
     */
    public function __invoke(LandingPageStatsService $stats): JsonResponse
    {
        return response()->json([
            'items' => $stats->getLiveFeedItems(),
        ]);
    }
}

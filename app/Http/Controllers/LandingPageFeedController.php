<?php

namespace App\Http\Controllers;

use App\Services\LandingPageStatsService;
use Illuminate\Http\JsonResponse;

class LandingPageFeedController extends Controller
{
    /**
     * Uncached live snapshot for the home hero ticker; respects session locale.
     * Returns hero numbers + feed items in one call so both update together.
     */
    public function __invoke(LandingPageStatsService $stats): JsonResponse
    {
        $snapshot = $stats->getLiveSnapshot();

        return response()->json([
            'items'            => $snapshot['feedItems'],
            'totalDelivered'   => $snapshot['totalDelivered'],
            'familiesSupported' => $snapshot['familiesSupported'],
            'localProviders'   => $snapshot['localProviders'],
        ]);
    }
}

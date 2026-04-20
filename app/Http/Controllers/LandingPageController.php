<?php

namespace App\Http\Controllers;

use App\Services\LandingPageStatsService;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function __invoke(LandingPageStatsService $stats): View
    {
        return view('welcome', [
            'heroStats' => $stats->getHeroStats(),
        ]);
    }
}

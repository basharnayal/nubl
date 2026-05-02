<?php

namespace App\Http\Controllers;

use App\Services\TopDonorsService;
use Illuminate\View\View;

class TopDonorsController extends Controller
{
    public function __invoke(TopDonorsService $service): View
    {
        return view('top-donors.index', [
            'donors' => $service->getTopDonors(100),
        ]);
    }
}

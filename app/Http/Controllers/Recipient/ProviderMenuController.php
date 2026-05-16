<?php

namespace App\Http\Controllers\Recipient;

use App\Http\Controllers\Controller;
use App\Models\ProviderMenuItem;
use App\Models\User;
use App\Services\Recipient\AllowanceService;
use App\Services\Recipient\ProviderService;
use Illuminate\Http\Request;

class ProviderMenuController extends Controller
{
    public function __construct(
        private ProviderService $providerService
    ) {}

    /**
     * Display a listing of providers.
     */
    public function index(Request $request)
    {
        $providers = $this->providerService->listProviders($request);

        return view('recipient.providers.index', compact('providers'));
    }

    /**
     * Display the specified provider and their menu.
     */
    public function show(User $provider, Request $request)
    {
        $data = $this->providerService->getProviderMenu($provider, $request);

        return view('recipient.providers.show', $data);
    }
}

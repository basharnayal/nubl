<?php

namespace App\Http\Controllers\Auth;

use App\Auth\PostAuthRedirector;
use App\Auth\Registration\Data\ProviderRegistrationData;
use App\Auth\Registration\RegistrationManager;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreProviderRegistrationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Provider registration: 4-step form → pending_approval until admin approves.
 * Flow: /register → select Provider → /register/provider → submit → approval-pending.
 */
class ProviderRegistrationController extends Controller
{
    public function __construct(
        private RegistrationManager $registrationManager,
        private PostAuthRedirector $postAuthRedirector,
    ) {}

    public function create(Request $request): View|RedirectResponse
    {
        if (auth()->check() && auth()->user()->providerProfile) {
            return $this->showApplication($request);
        }

        return view('auth.register-provider', [
            'providerData' => null,
            'businessCategories' => config('provider.business_categories'),
            'serviceTypes' => config('provider.service_types'),
            'weekdays' => config('provider.weekdays'),
            'documentMaxSizeMb' => config('provider.document_max_size_mb', 5),
        ]);
    }

    public function showApplication(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if (! $user?->providerProfile) {
            return redirect()->route('register.provider');
        }

        return view('auth.provider-application', [
            'providerData' => [
                'profile' => $user->providerProfile,
                'operating' => $user->providerOperatingInfo,
                'financial' => $user->providerFinancialInfo,
                'documents' => $user->providerDocuments,
            ],
        ]);
    }

    public function store(StoreProviderRegistrationRequest $request): RedirectResponse
    {
        $user = $this->registrationManager->register(
            ProviderRegistrationData::fromRequest($request)
        );

        return $this->postAuthRedirector->redirect($user);
    }
}

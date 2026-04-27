<?php

namespace App\Http\Controllers\Auth;

use App\Auth\PostAuthRedirector;
use App\Auth\Registration\Data\DonorRegistrationData;
use App\Auth\Registration\Data\RecipientRegistrationData;
use App\Auth\Registration\RegistrationManager;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\StoreRegisteredUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Handles /register for donor and recipient flows.
 * All business logic lives in RegistrationManager + the concrete Registrars.
 */
class RegisteredUserController extends Controller
{
    public function __construct(
        private RegistrationManager $registrationManager,
        private PostAuthRedirector $postAuthRedirector,
    ) {}

    public function create(): View
    {
        return view('auth.register');
    }

    public function store(StoreRegisteredUserRequest $request): RedirectResponse
    {
        $data = $request->validated('membership_type') === User::MEMBERSHIP_DONOR
            ? DonorRegistrationData::fromRequest($request)
            : RecipientRegistrationData::fromRequest($request);

        $user = $this->registrationManager->register($data);

        return $this->postAuthRedirector->redirect($user);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\UpdateProviderBusinessProfileRequest;
use App\Http\Requests\UpdateProviderFinancialProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();
        $providerProfile = null;
        $providerFinancial = null;
        if ($user->hasRole('provider')) {
            $user->loadMissing(['providerProfile', 'providerFinancialInfo']);
            $providerProfile = $user->providerProfile;
            $providerFinancial = $user->providerFinancialInfo;
        }

        return view('profile.edit', [
            'user' => $user,
            'providerProfile' => $providerProfile,
            'providerFinancial' => $providerFinancial,
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($user->hasRole('provider') && $user->providerProfile) {
            $user->providerProfile->update(['email' => $user->email]);
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Update provider business identity and contact (not operating hours — see provider profile edit).
     */
    public function updateProviderBusiness(UpdateProviderBusinessProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $profile = $user->providerProfile;
        abort_unless($profile, 404);

        $payload = $request->businessProfilePayload();

        DB::transaction(function () use ($user, $profile, $payload) {
            $profile->update([
                'full_name_ar' => $payload['full_name_ar'],
                'full_name_en' => $payload['full_name_en'],
                'phone_number' => $payload['phone_normalized'],
                'email' => $user->email,
                'business_name_ar' => $payload['business_name_ar'],
                'business_name_en' => $payload['business_name_en'],
                'business_category' => $payload['business_category'],
                'address_ar' => $payload['address_ar'],
                'address_en' => $payload['address_en'],
                'city' => $payload['city'],
                'region' => $payload['region'],
                'location' => $payload['location'],
            ]);

            $user->update([
                'name' => $payload['full_name_en'],
                'phone_number' => $payload['phone_normalized'],
            ]);
        });

        return Redirect::route('profile.edit')->with('status', 'business-profile-updated');
    }

    /**
     * Update provider payout / bank details (same fields as registration step 3).
     */
    public function updateProviderFinancial(UpdateProviderFinancialProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $financial = $user->providerFinancialInfo;
        abort_unless($financial, 404);

        $validated = $request->validated();

        $financial->update([
            'bank_name' => $validated['bank_name'],
            'iban' => $validated['iban'],
            'account_holder_name' => $validated['account_holder_name'],
        ]);

        return Redirect::route('profile.edit')->with('status', 'financial-profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}

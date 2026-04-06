<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\UpdateProviderBusinessProfileRequest;
use App\Http\Requests\UpdateProviderFinancialProfileRequest;
use App\Http\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        private AuditService $auditService
    ) {}

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

        $changedAttributes = array_keys($user->getDirty());
        $user->save();

        if ($user->hasRole('provider') && $user->providerProfile) {
            $user->providerProfile->update(['email' => $user->email]);
        }

        if ($user->hasRole('provider') && $changedAttributes !== []) {
            $this->auditService->log('provider_account', 'updated', [
                'user_id' => $user->id,
                'changed_attributes' => $changedAttributes,
            ]);
        }

        if ($user->hasRole('recipient') && $changedAttributes !== []) {
            $this->auditService->log('recipient_account', 'updated', [
                'user_id' => $user->id,
                'changed_attributes' => $changedAttributes,
            ]);
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

        $this->auditService->log('provider_profile', 'business_updated', [
            'user_id' => $user->id,
            'provider_profile_id' => $profile->id,
        ]);

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

        $this->auditService->log('provider_financial', 'updated', [
            'user_id' => $user->id,
            'provider_financial_info_id' => $financial->id,
            'bank_name' => $validated['bank_name'],
            'iban_updated' => true,
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

        $this->auditService->log('account', 'deleted', [
            'user_id' => $user->id,
            'membership_type' => $user->membership_type,
        ], $user->id);

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}

<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProviderProfileController extends Controller
{
    /**
     * Show the form for editing the specified resource.
     */
    public function edit()
    {
        $user = auth()->user();
        $profile = $user->providerProfile;
        $operatingInfo = $user->providerOperatingInfo;

        return view('provider.profile.edit', compact('user', 'profile', 'operatingInfo'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        // ... (Usually handles profile updates, skip for now unless requested)
    }

    /**
     * Toggle the provider's active status.
     */
    public function toggleActive(Request $request)
    {
        $user = auth()->user();

        // Toggle
        $user->is_active = !$user->is_active;
        $user->save();

        $status = $user->is_active ? 'active' : 'inactive';

        return back()->with('success', "You are now {$status}. Recipients " . ($user->is_active ? "can" : "cannot") . " see your menu.");
    }
}

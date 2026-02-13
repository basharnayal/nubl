<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AccountApprovalController extends Controller
{
    public function index(): View
    {
        $pendingUsers = User::whereIn('status', [User::STATUS_PENDING_APPROVAL, User::STATUS_REJECTED])
            ->whereIn('membership_type', [User::MEMBERSHIP_RECIPIENT, User::MEMBERSHIP_PROVIDER])
            ->with(['recipientProfile', 'recipientKycDetails', 'providerProfile', 'providerDocuments'])
            ->orderByRaw("CASE WHEN status = ? THEN 0 ELSE 1 END", [User::STATUS_PENDING_APPROVAL])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.users.pending', compact('pendingUsers'));
    }

    public function approve(User $user): RedirectResponse
    {
        if (!in_array($user->status, [User::STATUS_PENDING_APPROVAL, User::STATUS_REJECTED])) {
            return redirect()->route('admin.users.pending')->with('error', __('Invalid request.'));
        }

        $user->update(['status' => User::STATUS_ACTIVE, 'rejection_reason' => null]);

        return redirect()->route('admin.users.pending')->with('success', __('Account approved successfully.'));
    }

    public function showRejectForm(User $user): View|RedirectResponse
    {
        if ($user->status !== User::STATUS_PENDING_APPROVAL) {
            return redirect()->route('admin.users.pending')->with('error', __('Invalid request.'));
        }

        return view('admin.users.reject-form', compact('user'));
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        if ($user->status !== User::STATUS_PENDING_APPROVAL) {
            return redirect()->route('admin.users.pending')->with('error', __('Invalid request.'));
        }

        $validated = $request->validate([
            'rejection_reason' => ['required', 'string', 'max:2000'],
        ]);

        $user->update([
            'status' => User::STATUS_REJECTED,
            'rejection_reason' => $validated['rejection_reason'],
        ]);

        return redirect()->route('admin.users.pending')->with('success', __('Account rejected.'));
    }

    public function showApplication(User $user): View|RedirectResponse
    {
        if ($user->membership_type === User::MEMBERSHIP_PROVIDER && $user->providerProfile) {
            return $this->showProviderApplication($user);
        }
        if ($user->membership_type === User::MEMBERSHIP_RECIPIENT && $user->recipientProfile) {
            return $this->showRecipientApplication($user);
        }

        return redirect()->route('admin.users.pending')->with('error', __('No application found.'));
    }

    public function showProviderApplication(User $user): View|RedirectResponse
    {
        if (!$user->providerProfile) {
            return redirect()->route('admin.users.pending')->with('error', __('No provider application found.'));
        }

        return view('admin.users.provider-application', [
            'user' => $user,
            'providerData' => [
                'profile' => $user->providerProfile,
                'operating' => $user->providerOperatingInfo,
                'financial' => $user->providerFinancialInfo,
                'documents' => $user->providerDocuments,
            ],
        ]);
    }

    public function showRecipientApplication(User $user): View|RedirectResponse
    {
        if (!$user->recipientProfile) {
            return redirect()->route('admin.users.pending')->with('error', __('No recipient application found.'));
        }

        return view('admin.users.recipient-application', [
            'user' => $user,
        ]);
    }

    public function serveFile(User $user, string $type): StreamedResponse|RedirectResponse
    {
        $path = null;

        if ($user->membership_type === User::MEMBERSHIP_PROVIDER && $user->providerDocuments) {
            $path = match ($type) {
                'business_license' => $user->providerDocuments->business_license_path,
                'id_or_iqama' => $user->providerDocuments->id_or_iqama_path,
                default => null,
            };
        } elseif ($user->membership_type === User::MEMBERSHIP_RECIPIENT) {
            if ($type === 'id_photo' && $user->recipientProfile) {
                $path = $user->recipientProfile->id_photo_path;
            } elseif ($type === 'address_confirmation' && $user->recipientKycDetails) {
                $path = $user->recipientKycDetails->address_confirmation;
            }
        }

        if (!$path || !Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->response($path);
    }
}

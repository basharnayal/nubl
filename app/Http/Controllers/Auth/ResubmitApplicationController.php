<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdateResubmitApplicationRequest;
use App\Http\Services\AuditService;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Rejected recipients/providers edit the same application data shown on the admin review screen.
 * Status returns to pending_approval; admin notification via UserObserver.
 */
class ResubmitApplicationController extends Controller
{
    public function __construct(
        private AuditService $auditService
    ) {}

    public function edit(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if ($user->status !== User::STATUS_REJECTED) {
            return redirect()->route('approval.pending');
        }
        if (! in_array($user->membership_type, [User::MEMBERSHIP_RECIPIENT, User::MEMBERSHIP_PROVIDER], true)) {
            abort(403);
        }

        if ($user->membership_type === User::MEMBERSHIP_PROVIDER) {
            $user->load(['providerProfile', 'providerOperatingInfo', 'providerFinancialInfo', 'providerDocuments']);

            return view('auth.resubmit-provider', [
                'user' => $user,
                'profile' => $user->providerProfile,
                'operating' => $user->providerOperatingInfo,
                'financial' => $user->providerFinancialInfo,
                'documents' => $user->providerDocuments,
                'businessCategories' => config('provider.business_categories'),
                'serviceTypes' => config('provider.service_types'),
                'weekdays' => config('provider.weekdays'),
                'documentMaxSizeMb' => config('provider.document_max_size_mb', 5),
            ]);
        }

        $user->load(['recipientProfile', 'recipientKycDetails']);

        return view('auth.resubmit-recipient', [
            'user' => $user,
            'profile' => $user->recipientProfile,
            'kyc' => $user->recipientKycDetails,
        ]);
    }

    /**
     * Serve current application documents to the authenticated applicant only (for previews on resubmit form).
     */
    public function serveFile(Request $request, string $type): StreamedResponse|RedirectResponse
    {
        $user = $request->user();
        $user->loadMissing(['providerDocuments', 'recipientProfile', 'recipientKycDetails']);
        $path = null;

        if ($user->membership_type === User::MEMBERSHIP_PROVIDER && $user->providerDocuments) {
            $path = match ($type) {
                'business_license' => $user->providerDocuments->business_license_path,
                'id_or_iqama' => $user->providerDocuments->id_or_iqama_path,
                default => null,
            };
        } elseif ($user->membership_type === User::MEMBERSHIP_RECIPIENT) {
            $user->loadMissing(['recipientProfile', 'recipientKycDetails']);
            if ($type === 'id_photo' && $user->recipientProfile) {
                $path = $user->recipientProfile->id_photo_path;
            } elseif ($type === 'address_confirmation' && $user->recipientKycDetails) {
                $path = $user->recipientKycDetails->address_confirmation;
            }
        }

        if (! $path || ! Storage::disk('local')->exists($path)) {
            abort(404);
        }

        return Storage::disk('local')->response($path);
    }

    public function update(UpdateResubmitApplicationRequest $request): RedirectResponse
    {
        $user = $request->user();

        return $user->membership_type === User::MEMBERSHIP_RECIPIENT
            ? $this->updateRecipient($request, $user)
            : $this->updateProvider($request, $user);
    }

    private function updateRecipient(UpdateResubmitApplicationRequest $request, User $user): RedirectResponse
    {
        $user->load(['recipientProfile', 'recipientKycDetails']);
        $profile = $user->recipientProfile;
        $kyc = $user->recipientKycDetails;
        if (! $profile || ! $kyc) {
            return redirect()->route('approval.pending')->with('error', __('Application data is incomplete.'));
        }

        $validated = $request->validated();

        $idPath = null;
        $addrPath = null;
        if ($request->filled('id_photo_base64')) {
            $idPath = $this->storeBase64Image($validated['id_photo_base64'], 'recipient_id_photos');
        }
        if ($request->filled('address_confirmation_base64')) {
            $addrPath = $this->storeBase64Image($validated['address_confirmation_base64'], 'recipient_address_photos');
        }

        $previousIdPath = $profile->id_photo_path;
        $previousAddrPath = $kyc->address_confirmation;

        try {
            DB::transaction(function () use ($user, $profile, $kyc, $validated, $idPath, $addrPath, $previousIdPath, $previousAddrPath) {
                $user->update([
                    'name' => $validated['name'],
                    'status' => User::STATUS_PENDING_APPROVAL,
                    'rejection_reason' => null,
                ]);

                $profile->update([
                    'nationality' => $validated['nationality'],
                    'short_address' => $validated['short_address'],
                    'id_type' => $validated['id_type'],
                    'id_photo_path' => $idPath ?? $previousIdPath,
                ]);

                if ($idPath && $previousIdPath) {
                    Storage::disk('local')->delete($previousIdPath);
                }

                $kyc->update([
                    'income_band' => $validated['income_band'],
                    'household_size' => (int) $validated['household_size'],
                    'marital_status' => $validated['marital_status'],
                    'is_student' => (bool) (int) $validated['is_student'],
                    'address_confirmation' => $addrPath ?? $previousAddrPath,
                ]);

                if ($addrPath && $previousAddrPath) {
                    Storage::disk('local')->delete($previousAddrPath);
                }
            });
        } catch (\Throwable $e) {
            if ($idPath) {
                Storage::disk('local')->delete($idPath);
            }
            if ($addrPath) {
                Storage::disk('local')->delete($addrPath);
            }
            throw $e;
        }

        $this->auditService->log('application', 'resubmitted', [
            'user_id' => $user->id,
            'membership_type' => $user->membership_type,
        ], $user->id);

        return redirect()->route('approval.pending')->with('success', __('Your application has been submitted for review.'));
    }

    private function updateProvider(UpdateResubmitApplicationRequest $request, User $user): RedirectResponse
    {
        $user->load(['providerProfile', 'providerOperatingInfo', 'providerFinancialInfo', 'providerDocuments']);

        $validated = $request->validated();
        $operatingHours = $request->normalizedOperatingHours();

        $profile = $user->providerProfile;
        $operating = $user->providerOperatingInfo;
        $financial = $user->providerFinancialInfo;
        $docs = $user->providerDocuments;

        if (! $profile || ! $operating || ! $financial || ! $docs) {
            return redirect()->route('approval.pending')->with('error', __('Application data is incomplete.'));
        }

        $licensePath = null;
        $idDocPath = null;
        if ($request->hasFile('business_license')) {
            $licensePath = $request->file('business_license')->store('provider_documents', 'local');
        }
        if ($request->hasFile('id_or_iqama')) {
            $idDocPath = $request->file('id_or_iqama')->store('provider_documents', 'local');
        }

        try {
            DB::transaction(function () use ($user, $profile, $operating, $financial, $docs, $validated, $operatingHours, $licensePath, $idDocPath) {
                $userUpdates = [
                    'name' => $validated['full_name_en'],
                    'status' => User::STATUS_PENDING_APPROVAL,
                    'rejection_reason' => null,
                ];
                if (! empty($validated['password'])) {
                    $userUpdates['password'] = Hash::make($validated['password']);
                }
                $user->update($userUpdates);

                $profile->update([
                    'full_name_ar' => $validated['full_name_ar'],
                    'full_name_en' => $validated['full_name_en'],
                    'business_name_ar' => $validated['business_name_ar'],
                    'business_name_en' => $validated['business_name_en'],
                    'unified_number' => $validated['unified_number'],
                    'business_category' => $validated['business_category'],
                    'address_ar' => $validated['address_ar'],
                    'address_en' => $validated['address_en'],
                    'city' => $validated['city'],
                    'region' => $validated['region'],
                    'location' => $validated['location'] ?? null,
                ]);

                $operating->update([
                    'operating_hours' => $operatingHours,
                    'daily_capacity' => $validated['daily_capacity'],
                    'service_type' => $validated['service_type'],
                    'estimated_preparation_order_time' => $validated['estimated_preparation_order_time'],
                    'adoption_support' => $validated['adoption_support'],
                ]);

                $financial->update([
                    'bank_name' => $validated['bank_name'],
                    'iban' => $validated['iban'],
                    'account_holder_name' => $validated['account_holder_name'],
                ]);

                $docUpdates = [];
                if ($licensePath) {
                    Storage::disk('local')->delete($docs->business_license_path ?? '');
                    $docUpdates['business_license_path'] = $licensePath;
                }
                if ($idDocPath) {
                    Storage::disk('local')->delete($docs->id_or_iqama_path ?? '');
                    $docUpdates['id_or_iqama_path'] = $idDocPath;
                }
                if ($docUpdates !== []) {
                    $docs->update($docUpdates);
                }
            });
        } catch (\Throwable $e) {
            if ($licensePath) {
                Storage::disk('local')->delete($licensePath);
            }
            if ($idDocPath) {
                Storage::disk('local')->delete($idDocPath);
            }
            throw $e;
        }

        $this->auditService->log('application', 'resubmitted', [
            'user_id' => $user->id,
            'membership_type' => $user->membership_type,
        ], $user->id);

        return redirect()->route('approval.pending')->with('success', __('Your application has been submitted for review.'));
    }

    private function storeBase64Image(string $base64Data, string $directory): string
    {
        preg_match('/^data:image\/(jpeg|jpg|png|webp);base64,/i', $base64Data, $matches);
        $extension = $matches[1] ?? 'jpg';
        $extension = $extension === 'jpg' ? 'jpeg' : $extension;

        $base64 = preg_replace('/^data:image\/(jpeg|jpg|png|webp);base64,/', '', $base64Data);
        $decoded = base64_decode($base64, true);

        $filename = uniqid('resubmit_', true).'.'.$extension;
        $path = $directory.'/'.$filename;

        Storage::disk('local')->put($path, $decoded);

        return $path;
    }
}

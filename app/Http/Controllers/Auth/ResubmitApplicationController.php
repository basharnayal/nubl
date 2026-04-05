<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Services\AuditService;
use App\Models\RecipientKycDetails;
use App\Models\RecipientProfile;
use App\Models\User;
use App\Rules\Base64Image;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Rejected recipients/providers edit the same application data shown on the admin review screen.
 * Status returns to pending_approval; admin notification via UserObserver.
 */
class ResubmitApplicationController extends Controller
{
    public function __construct(
        private AuditService $auditService,
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

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user->status !== User::STATUS_REJECTED) {
            return redirect()->route('approval.pending');
        }
        if (! in_array($user->membership_type, [User::MEMBERSHIP_RECIPIENT, User::MEMBERSHIP_PROVIDER], true)) {
            abort(403);
        }

        if ($user->membership_type === User::MEMBERSHIP_RECIPIENT) {
            return $this->updateRecipient($request, $user);
        }

        return $this->updateProvider($request, $user);
    }

    private function updateRecipient(Request $request, User $user): RedirectResponse
    {
        $user->load(['recipientProfile', 'recipientKycDetails']);
        $profile = $user->recipientProfile;
        $kyc = $user->recipientKycDetails;
        if (! $profile || ! $kyc) {
            return redirect()->route('approval.pending')->with('error', __('Application data is incomplete.'));
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nationality' => ['required', 'string', 'in:'.implode(',', config('nationalities'))],
            'short_address' => ['required', 'string', 'max:500'],
            'id_type' => ['required', 'string', 'in:'.implode(',', RecipientProfile::ID_TYPES)],
            'income_band' => ['required', 'string', 'in:'.implode(',', RecipientKycDetails::INCOME_BANDS)],
            'household_size' => ['required', 'integer', 'min:1', 'max:50'],
            'marital_status' => ['required', 'string', 'in:'.implode(',', RecipientKycDetails::MARITAL_STATUSES)],
            'is_student' => ['required', 'in:0,1'],
            'id_photo_base64' => ['nullable', 'string'],
            'address_confirmation_base64' => ['nullable', 'string'],
        ]);

        if ($request->filled('id_photo_base64')) {
            $request->validate(['id_photo_base64' => [new Base64Image]]);
        }
        if ($request->filled('address_confirmation_base64')) {
            $request->validate(['address_confirmation_base64' => [new Base64Image]]);
        }

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

        $this->auditService->log('recipient_application', 'resubmitted', [
            'user_id' => $user->id,
            'recipient_profile_id' => $profile->id,
            'id_photo_updated' => (bool) $idPath,
            'address_confirmation_updated' => (bool) $addrPath,
        ]);

        return redirect()->route('approval.pending')->with('success', __('Your application has been submitted for review.'));
    }

    private function updateProvider(Request $request, User $user): RedirectResponse
    {
        $user->load(['providerProfile', 'providerOperatingInfo', 'providerFinancialInfo', 'providerDocuments']);

        $maxMb = config('provider.document_max_size_mb', 5);
        $maxBytes = $maxMb * 1024 * 1024;

        $validated = $request->validate([
            'full_name_ar' => ['required', 'string', 'max:255'],
            'full_name_en' => ['required', 'string', 'max:255'],
            'business_name_ar' => ['required', 'string', 'max:255'],
            'business_name_en' => ['required', 'string', 'max:255'],
            'unified_number' => ['required', 'string', 'max:50'],
            'business_category' => ['required', 'array', 'min:1'],
            'business_category.*' => ['string', 'in:'.implode(',', config('provider.business_categories'))],
            'address_ar' => ['required', 'string', 'max:1000'],
            'address_en' => ['required', 'string', 'max:1000'],
            'city' => ['required', 'string', 'in:'.implode(',', array_keys(config('provider.cities', [])))],
            'region' => ['required', 'string', 'in:'.implode(',', array_keys(config('provider.regions', [])))],
            'location' => ['nullable', 'string', 'max:500'],
            'daily_capacity' => ['required', 'integer', 'min:1', 'max:10000'],
            'service_type' => ['required', 'array', 'min:1'],
            'service_type.*' => ['string', 'in:'.implode(',', config('provider.service_types'))],
            'estimated_preparation_order_time' => ['required', 'string', 'max:100'],
            'adoption_support' => ['required', 'string', 'in:yes,partially,no'],
            'bank_name' => ['required', 'string', 'max:255'],
            'iban' => ['required', 'string', 'max:50'],
            'account_holder_name' => ['required', 'string', 'max:255'],
            'business_license' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.$maxBytes],
            'id_or_iqama' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:'.$maxBytes],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $request->validate([
            'operating_hours' => ['required', 'array'],
            ...collect(array_keys(config('provider.weekdays')))->mapWithKeys(fn ($d) => ["operating_hours.{$d}" => ['required', 'array']])->all(),
        ]);

        $operatingHours = $this->buildOperatingHoursFromRequest($request);

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
            DB::transaction(function () use ($user, $profile, $operating, $financial, $docs, $validated, $operatingHours, $licensePath, $idDocPath, $request) {
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

        return redirect()->route('approval.pending')->with('success', __('Your application has been submitted for review.'));
    }

    private function buildOperatingHoursFromRequest(Request $request): array
    {
        $operatingHours = [];
        $weekdays = array_keys(config('provider.weekdays'));
        $oh = $request->input('operating_hours', []);

        foreach ($weekdays as $day) {
            $dayData = $oh[$day] ?? [];
            $closed = filter_var($dayData['closed'] ?? false, FILTER_VALIDATE_BOOLEAN);
            if ($closed) {
                $operatingHours[$day] = ['closed' => true];
            } else {
                $open = trim($dayData['open'] ?? '');
                $close = trim($dayData['close'] ?? '');
                if (! $open || ! $close) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "operating_hours.{$day}" => [__('Set opening and closing time, or mark as closed.')],
                    ]);
                }
                $operatingHours[$day] = ['open' => $open, 'close' => $close, 'closed' => false];
            }
        }

        return $operatingHours;
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

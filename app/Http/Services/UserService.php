<?php

namespace App\Http\Services;

use App\Models\ProviderDocuments;
use App\Models\ProviderFinancialInfo;
use App\Models\ProviderOperatingInfo;
use App\Models\ProviderProfile;
use App\Models\RecipientKycDetails;
use App\Models\RecipientProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UserService
{
    /**
     * Create user with role and type-specific profiles.
     *
     * @throws \Throwable
     */
    public function createUser(array $data, Request $request): User
    {
        return DB::transaction(function () use ($data, $request) {
            $user = $this->createUserRecord($data);
            $this->createProfileByType($user, $data, $request);

            return $user;
        });
    }

    /**
     * Update user and type-specific profiles.
     *
     * @throws \Throwable
     */
    public function updateUser(User $user, array $data, Request $request): void
    {
        DB::transaction(function () use ($user, $data, $request) {
            $this->updateUserRecord($user, $data);
            $this->updateProfileByType($user, $data, $request);
        });
    }

    /**
     * Delete user. Guards: prevent self-delete, prevent deleting last active admin.
     *
     * @throws \InvalidArgumentException If target is current admin or last active admin
     */
    public function deleteUser(User $user, User $admin): void
    {
        $this->guardDelete($user, $admin);

        $user->delete();
    }

    /**
     * Deactivate user. Blocks login. Logs action.
     *
     * @throws \InvalidArgumentException If target is current admin or last admin
     */
    public function deactivate(User $user, User $admin): void
    {
        $this->guardDeactivate($user, $admin);

        $user->deactivate();

        Log::info('User deactivated', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'admin_id' => $admin->id,
            'admin_email' => $admin->email,
        ]);
    }

    /**
     * Reactivate user. Logs action.
     */
    public function reactivate(User $user, User $admin): void
    {
        $user->reactivate();

        Log::info('User reactivated', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'admin_id' => $admin->id,
            'admin_email' => $admin->email,
        ]);
    }

    /**
     * Prevent admin from deleting self or last active admin.
     */
    protected function guardDelete(User $user, User $admin): void
    {
        if ($user->id === $admin->id) {
            throw new \InvalidArgumentException(__('You cannot delete your own account.'));
        }

        if ($user->hasRole('admin')) {
            $activeAdminCount = User::role('admin')->where('is_active', true)->count();
            if ($activeAdminCount <= 1) {
                throw new \InvalidArgumentException(__('Cannot delete the last active administrator.'));
            }
        }
    }

    /**
     * Prevent admin from deactivating self or last admin.
     */
    protected function guardDeactivate(User $user, User $admin): void
    {
        if ($user->id === $admin->id) {
            throw new \InvalidArgumentException(__('You cannot deactivate your own account.'));
        }

        if ($user->hasRole('admin')) {
            $activeAdminCount = User::role('admin')->where('is_active', true)->count();
            if ($activeAdminCount <= 1) {
                throw new \InvalidArgumentException(__('Cannot deactivate the last active administrator.'));
            }
        }
    }

    protected function createUserRecord(array $data): User
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'membership_type' => $data['membership_type'],
            'status' => User::STATUS_ACTIVE,
            'phone_number' => $data['phone_number'] ?? null,
            'is_active' => true,
        ]);

        $role = match ($data['membership_type']) {
            'donor' => 'donor',
            'recipient' => 'recipient',
            'provider' => 'provider',
            default => 'donor',
        };
        $user->assignRole($role);

        return $user;
    }

    protected function createProfileByType(User $user, array $data, Request $request): void
    {
        if ($user->membership_type === User::MEMBERSHIP_RECIPIENT) {
            $idPath = $request->file('id_photo')->store('recipient_id_photos', 'local');
            $addrPath = $request->file('address_confirmation')->store('recipient_address_photos', 'local');

            RecipientProfile::create([
                'user_id' => $user->id,
                'nationality' => $data['nationality'],
                'short_address' => $data['short_address'],
                'id_type' => $data['id_type'],
                'id_photo_path' => $idPath,
            ]);

            RecipientKycDetails::create([
                'user_id' => $user->id,
                'income_band' => $data['income_band'],
                'household_size' => (int) $data['household_size'],
                'marital_status' => $data['marital_status'],
                'is_student' => (bool) $data['is_student'],
                'address_confirmation' => $addrPath,
            ]);
        }

        if ($user->membership_type === User::MEMBERSHIP_PROVIDER) {
            $licensePath = $request->file('business_license')->store('provider_documents', 'local');
            $idPath = $request->file('id_or_iqama')->store('provider_documents', 'local');

            $operatingHours = [];
            foreach (array_keys(config('provider.weekdays')) as $day) {
                $operatingHours[$day] = ['closed' => true];
            }

            ProviderProfile::create([
                'user_id' => $user->id,
                'full_name_ar' => $data['full_name_ar'],
                'full_name_en' => $data['full_name_en'],
                'phone_number' => $data['phone_number'],
                'email' => $data['email'],
                'business_name_ar' => $data['business_name_ar'],
                'business_name_en' => $data['business_name_en'],
                'unified_number' => $data['unified_number'],
                'business_category' => $data['business_category'],
                'address_ar' => $data['address_ar'],
                'address_en' => $data['address_en'],
                'city' => $data['city'],
                'region' => $data['region'],
                'location' => $data['location'] ?? null,
            ]);

            ProviderOperatingInfo::create([
                'user_id' => $user->id,
                'operating_hours' => $operatingHours,
                'daily_capacity' => $data['daily_capacity'],
                'service_type' => $data['service_type'],
                'estimated_preparation_order_time' => $data['estimated_preparation_order_time'],
                'adoption_support' => $data['adoption_support'],
            ]);

            ProviderFinancialInfo::create([
                'user_id' => $user->id,
                'bank_name' => $data['bank_name'],
                'iban' => $data['iban'],
                'account_holder_name' => $data['account_holder_name'],
            ]);

            ProviderDocuments::create([
                'user_id' => $user->id,
                'business_license_path' => $licensePath,
                'id_or_iqama_path' => $idPath,
            ]);
        }
    }

    protected function updateUserRecord(User $user, array $data): void
    {
        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'membership_type' => $data['membership_type'],
            'phone_number' => $data['phone_number'] ?? null,
        ];

        if (! empty($data['password'] ?? '')) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);
        $user->syncRoles([$data['membership_type']]);
    }

    protected function updateProfileByType(User $user, array $data, Request $request): void
    {
        if ($user->membership_type === User::MEMBERSHIP_RECIPIENT && $user->recipientProfile) {
            $profile = $user->recipientProfile;
            $kyc = $user->recipientKycDetails;

            $profileData = [
                'nationality' => $data['nationality'],
                'short_address' => $data['short_address'],
                'id_type' => $data['id_type'],
            ];
            if ($request->hasFile('id_photo')) {
                Storage::disk('local')->delete($profile->id_photo_path ?? '');
                $profileData['id_photo_path'] = $request->file('id_photo')->store('recipient_id_photos', 'local');
            }
            $profile->update($profileData);

            $kycData = [
                'income_band' => $data['income_band'],
                'household_size' => (int) $data['household_size'],
                'marital_status' => $data['marital_status'],
                'is_student' => (bool) $data['is_student'],
            ];
            if ($request->hasFile('address_confirmation')) {
                Storage::disk('local')->delete($kyc->address_confirmation ?? '');
                $kycData['address_confirmation'] = $request->file('address_confirmation')->store('recipient_address_photos', 'local');
            }
            $kyc->update($kycData);
        }

        if ($user->membership_type === User::MEMBERSHIP_PROVIDER && $user->providerProfile) {
            $profile = $user->providerProfile;
            $operating = $user->providerOperatingInfo;
            $financial = $user->providerFinancialInfo;
            $docs = $user->providerDocuments;

            $profile->update([
                'full_name_ar' => $data['full_name_ar'],
                'full_name_en' => $data['full_name_en'],
                'phone_number' => $data['phone_number'],
                'email' => $data['email'],
                'business_name_ar' => $data['business_name_ar'],
                'business_name_en' => $data['business_name_en'],
                'unified_number' => $data['unified_number'],
                'business_category' => $data['business_category'],
                'address_ar' => $data['address_ar'],
                'address_en' => $data['address_en'],
                'city' => $data['city'],
                'region' => $data['region'],
                'location' => $data['location'] ?? null,
            ]);

            $operating->update([
                'daily_capacity' => $data['daily_capacity'],
                'service_type' => $data['service_type'],
                'estimated_preparation_order_time' => $data['estimated_preparation_order_time'],
                'adoption_support' => $data['adoption_support'],
            ]);

            $financial->update([
                'bank_name' => $data['bank_name'],
                'iban' => $data['iban'],
                'account_holder_name' => $data['account_holder_name'],
            ]);

            if ($request->hasFile('business_license')) {
                Storage::disk('local')->delete($docs->business_license_path ?? '');
                $docs->update(['business_license_path' => $request->file('business_license')->store('provider_documents', 'local')]);
            }
            if ($request->hasFile('id_or_iqama')) {
                Storage::disk('local')->delete($docs->id_or_iqama_path ?? '');
                $docs->update(['id_or_iqama_path' => $request->file('id_or_iqama')->store('provider_documents', 'local')]);
            }
        }
    }
}

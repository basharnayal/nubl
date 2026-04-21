<?php

use App\Support\PhoneHelper;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->normalizeUsers();
        $this->normalizeProviderProfiles();
        $this->deduplicateUsersPhone();

        Schema::table('users', function (Blueprint $table) {
            $table->unique('phone_number');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['phone_number']);
        });
    }

    protected function normalizeUsers(): void
    {
        User::whereNotNull('phone_number')->each(function (User $user) {
            try {
                $normalized = PhoneHelper::normalize($user->phone_number);
                if (PhoneHelper::isValid($user->phone_number)) {
                    $user->update(['phone_number' => $normalized]);
                }
            } catch (\Throwable) {
                // Skip invalid numbers
            }
        });
    }

    protected function normalizeProviderProfiles(): void
    {
        ProviderProfile::whereNotNull('phone_number')->each(function (ProviderProfile $profile) {
            try {
                $normalized = PhoneHelper::normalize($profile->phone_number);
                if (PhoneHelper::isValid($profile->phone_number)) {
                    $profile->update(['phone_number' => $normalized]);
                }
            } catch (\Throwable) {
                // Skip invalid numbers
            }
        });
    }

    /**
     * Keep first user per phone, set duplicates to null.
     */
    protected function deduplicateUsersPhone(): void
    {
        $duplicates = User::select('phone_number')
            ->whereNotNull('phone_number')
            ->groupBy('phone_number')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('phone_number');

        foreach ($duplicates as $phone) {
            $users = User::where('phone_number', $phone)->orderBy('id')->get();
            foreach ($users->skip(1) as $user) {
                $user->update(['phone_number' => null]);
            }
        }
    }
};

<?php

namespace App\Contracts;

use App\Models\User;

interface AllocationEngineServiceInterface
{
    public function pauseGlobal(User $admin): void;

    public function resumeGlobal(User $admin): void;

    public function pauseProvider(User $provider, User $admin): void;

    public function resumeProvider(User $provider, User $admin): void;

    public function isGloballyPaused(): bool;

    public function isProviderPaused(User $provider): bool;
}

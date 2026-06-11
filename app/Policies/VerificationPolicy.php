<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\Request as AppRequest;
use App\Models\User;

class VerificationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::AmlakasVerifier);
    }

    public function view(User $user, AppRequest $request): bool
    {
        return $user->hasRole(Role::AmlakasVerifier);
    }

    public function approve(User $user, AppRequest $request): bool
    {
        return $user->hasRole(Role::AmlakasVerifier) && $request->isSubmitted();
    }

    public function reject(User $user, AppRequest $request): bool
    {
        return $user->hasRole(Role::AmlakasVerifier) && $request->isSubmitted();
    }

    public function route(User $user, AppRequest $request): bool
    {
        return $user->hasRole(Role::AmlakasVerifier) && $request->isVerified();
    }
}

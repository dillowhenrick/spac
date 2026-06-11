<?php

namespace App\Policies;

use App\Enums\RequestStatus;
use App\Enums\Role;
use App\Models\Request;
use App\Models\RequestAssignment;
use App\Models\User;

class RequestPolicy
{
    // ── Requester ────────────────────────────────────────────────────────────

    public function viewAny(User $user): bool
    {
        return $user->hasRole(Role::Requester);
    }

    public function view(User $user, Request $request): bool
    {
        return $request->requester_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(Role::Requester);
    }

    public function submit(User $user, Request $request): bool
    {
        return $request->requester_id === $user->id && $request->isDraft();
    }

    // ── AMLakas Verifier ─────────────────────────────────────────────────────

    public function inspectAny(User $user): bool
    {
        return $user->hasRole(Role::AmlakasVerifier);
    }

    public function inspect(User $user, Request $request): bool
    {
        return $user->hasRole(Role::AmlakasVerifier);
    }

    public function approve(User $user, Request $request): bool
    {
        return $user->hasRole(Role::AmlakasVerifier) && $request->isSubmitted();
    }

    public function reject(User $user, Request $request): bool
    {
        return $user->hasRole(Role::AmlakasVerifier) && $request->isSubmitted();
    }

    public function routeRequest(User $user, Request $request): bool
    {
        return $user->hasRole(Role::AmlakasVerifier) && $request->isVerified();
    }

    // ── Institution Manager ───────────────────────────────────────────────────

    public function manageQueue(User $user): bool
    {
        return $user->hasRole(Role::InstitutionManager);
    }

    public function viewAsManager(User $user, Request $request): bool
    {
        return $user->isManagerOfInstitution($request->target_institution_id);
    }

    public function assign(User $user, Request $request): bool
    {
        return $user->isManagerOfInstitution($request->target_institution_id)
            && in_array($request->status, [
                RequestStatus::Routed,
                RequestStatus::Assigned,
                RequestStatus::InProgress,
            ], true);
    }

    // ── Institution Staff ─────────────────────────────────────────────────────

    public function viewAsStaff(User $user, Request $request): bool
    {
        return RequestAssignment::where('request_id', $request->id)
            ->where('assigned_to', $user->id)
            ->exists();
    }

    public function draftResponse(User $user, Request $request): bool
    {
        return RequestAssignment::where('request_id', $request->id)
            ->where('assigned_to', $user->id)
            ->exists();
    }

    public function submitResponse(User $user, Request $request): bool
    {
        if (! RequestAssignment::where('request_id', $request->id)->where('assigned_to', $user->id)->exists()) {
            return false;
        }

        return $request->response !== null && $request->response->isDraft();
    }

    public function approveResponse(User $user, Request $request): bool
    {
        return $user->isManagerOfInstitution($request->target_institution_id)
            && $request->response !== null
            && $request->response->isPendingApproval();
    }

    public function rejectResponse(User $user, Request $request): bool
    {
        return $user->isManagerOfInstitution($request->target_institution_id)
            && $request->response !== null
            && $request->response->isPendingApproval();
    }

    public function releaseResponse(User $user, Request $request): bool
    {
        return $user->isManagerOfInstitution($request->target_institution_id)
            && $request->response !== null
            && $request->response->isApproved()
            && $request->status === RequestStatus::Approved;
    }

    public function sendMessage(User $user, Request $request): bool
    {
        // Requester owns request
        if ($request->requester_id === $user->id) {
            return true;
        }

        // Manager of target institution
        if ($user->isManagerOfInstitution($request->target_institution_id)) {
            return true;
        }

        // Assigned staff
        return RequestAssignment::where('request_id', $request->id)
            ->where('assigned_to', $user->id)
            ->exists();
    }
}

<?php

namespace App\Http\Middleware;

use App\Models\Membership;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'memberships' => $request->user()
                    ? $request->user()
                        ->memberships()
                        ->with('organization')
                        ->get()
                        ->map(fn (Membership $membership) => [
                            'id' => $membership->id,
                            'role' => $membership->role->value,
                            'is_primary' => $membership->is_primary,
                            'organization_type' => $membership->organization_type,
                            'organization_id' => $membership->organization_id,
                            'organization_name' => $membership->organization?->name,
                        ])
                    : [],
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}

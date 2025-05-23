<?php

namespace App\Http\Middleware;

use App\Http\Resources\GroupResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $data = [
            ...parent::share($request),
            'auth' => [
                'user' => $request->user(),
            ],
            'notification' => session('notification', null)
        ];

        if (Auth::check()) {
            $user = User::with('ownedGroups.groupMembers', 'groups.groupMembers')->findOrFail($request->user()->id);
            $ownedGroups = GroupResource::collection($user->ownedGroups)->jsonSerialize();
            $memberGroups = GroupResource::collection($user->groups)->jsonSerialize();
            $allGroups = collect([...$ownedGroups, ...$memberGroups])
                ->unique('id') // removes duplicates by 'id'
                ->values()     // reindex
                ->all();
            $data['groups'] = $allGroups;
        }

        return $data;
    }
}

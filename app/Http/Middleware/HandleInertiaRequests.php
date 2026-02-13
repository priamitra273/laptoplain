<?php

namespace App\Http\Middleware;

use App\Facades\Sqids;
use App\Http\Resources\Menu\MenuSidebarResource;
use App\Models\Menu;
use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
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
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        $sidebar_menu = Menu::whereRelation('permissions.roles.users', 'id', Auth::id())
            ->whereNull('parent_id')
            ->orderBy('sequence_number')
            ->get();
        
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'quote' => [
                'message' => trim($message),
                'author' => trim($author),
            ],
            'auth' => [
                'user' => $user ? Sqids::rec_encode_ids_in_list($user->toArray()) : null,
                'role' => $user ? $user->getRoleNames()[0] : null,
                'menu' => MenuSidebarResource::collection($sidebar_menu)->resolve(),
            ],
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error'   => fn() => $request->session()->get('error'),
            ],
            'ziggy' => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
        ];
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminAccess
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        Log::debug('CHECK ADMIN ACCESS', [
            'user_id' => $user?->id,
            'role_names' => $user?->getRoleNames(),
            'roles_relation' => $user?->roles,
            'has_superadmin' => $user?->hasRole('superadmin'),
            'has_any_admin' => $user?->hasAnyRole(['superadmin', 'admin']),
            'roles_query' => $user?->roles()->pluck('name'),
        ]);

        abort(403, 'DEBUG STOP');
    }
}

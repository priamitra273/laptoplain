<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class CheckRoutePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            abort(401);
        }

        $routeName = $request->route()?->getName();

        if ($routeName == 'dashboard') {
            return $next($request);
        }

        if (!$routeName) {
            throw new NotFoundHttpException(404);
        }

        if ($user->getRoleNames()->contains(fn($role) => str_starts_with($role, 'super-admin-'))) {
            return $next($request);
        }

        $permission = $this->mapRouteToPermission($routeName);

        if (!$permission) {
            throw new NotFoundHttpException(404);
        }

        if ($user->can($permission)) {
            return $next($request);
        }

        if (str_ends_with($permission, '.read')) {
            throw new NotFoundHttpException(404);
        }

        return back()->with('error', 'You do not have permission to perform this action.');
    }

    protected function mapRouteToPermission(string $routeName): ?string
    {
        // task.index → ['task', 'index']
        $parts = explode('.', $routeName);

        if (count($parts) < 2) {
            return null;
        }

        $action = array_pop($parts); // index
        $resource = implode('.', $parts); // task

        $map = config('permission-map');

        if (!isset($map[$action])) {
            return null;
        }

        return $resource . '.' . $map[$action];
    }
}

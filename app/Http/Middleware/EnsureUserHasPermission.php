<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Auth\HomeRouteResolver;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    /**
     * Ensure the authenticated user has at least one of the required permissions.
     *
     * @param  array<int, string>  $permissions
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user || $permissions === [] || ! $user->hasAnyPermission($permissions)) {
            if ($user && ($redirect = $this->redirectToAllowedHome($request, $user))) {
                return $redirect;
            }

            abort(403);
        }

        return $next($request);
    }

    /**
     * Redirect authenticated users only when they tried to access a restricted home route.
     */
    protected function redirectToAllowedHome(Request $request, User $user): ?RedirectResponse
    {
        $currentRouteName = $request->route()?->getName();

        if (! in_array($currentRouteName, $this->redirectableHomeRoutes(), true)) {
            return null;
        }

        $homeRouteName = app(HomeRouteResolver::class)->routeNameFor($user);

        if (! $homeRouteName || $homeRouteName === $currentRouteName) {
            return null;
        }

        return redirect()->route($homeRouteName);
    }

    /**
     * Routes that act as permission-aware home destinations.
     *
     * @return array<int, string>
     */
    protected function redirectableHomeRoutes(): array
    {
        return [
            'dashboard',
            'doctor.home',
            'clerk.dispensation',
            'receptionist.requests',
        ];
    }
}

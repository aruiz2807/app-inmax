<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\Auth\HomeRouteResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasAllowedProfile
{
    /**
     * Ensure the authenticated user profile is in the allowed list.
     *
     * @param  array<int, string>  $profiles
     */
    public function handle(Request $request, Closure $next, string ...$profiles): Response
    {
        $user = $request->user();

        if (! $user || empty($profiles) || ! in_array($user->profile, $profiles, true)) {
            if ($user && ($redirect = $this->redirectToAllowedHome($request, $user))) {
                return $redirect;
            }

            abort(403);
        }

        return $next($request);
    }

    /**
     * Redirect authenticated users only when they tried to access another profile's home/dashboard.
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
     * Routes that act as profile home/dashboard destinations.
     *
     * @return array<int, string>
     */
    protected function redirectableHomeRoutes(): array
    {
        return [
            'dashboard',
            'user.home',
            'doctor.home',
            'clerk.dispensation',
            'receptionist.requests',
        ];
    }
}

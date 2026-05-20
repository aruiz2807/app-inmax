<?php

namespace App\Http\Middleware;

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
            if ($user && ($redirect = $this->redirectToAllowedHome($request, $user->profile))) {
                return $redirect;
            }

            abort(403);
        }

        return $next($request);
    }

    /**
     * Redirect authenticated users only when they tried to access another profile's home/dashboard.
     */
    protected function redirectToAllowedHome(Request $request, string $profile): ?RedirectResponse
    {
        $currentRouteName = $request->route()?->getName();

        if (! in_array($currentRouteName, $this->redirectableHomeRoutes(), true)) {
            return null;
        }

        $homeRouteName = $this->homeRouteNameForProfile($profile);

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

    /**
     * Resolve the allowed home route for a given profile.
     */
    protected function homeRouteNameForProfile(string $profile): ?string
    {
        return match ($profile) {
            'Admin', 'Sales' => 'dashboard',
            'User' => 'user.home',
            'Doctor' => 'doctor.home',
            'Clerk' => 'clerk.dispensation',
            'Receptionist' => 'receptionist.requests',
            default => null,
        };
    }
}

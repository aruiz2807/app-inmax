<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
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
            abort(403);
        }

        return $next($request);
    }
}

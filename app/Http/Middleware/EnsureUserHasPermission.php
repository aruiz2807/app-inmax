<?php

namespace App\Http\Middleware;

use Closure;
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
            abort(403);
        }

        return $next($request);
    }
}

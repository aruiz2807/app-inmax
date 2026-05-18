<?php

namespace App\Http\Middleware;

use App\Services\Auth\LoginRedirectResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StoreLastVisitedUrl
{
    /**
     * Store the last meaningful authenticated GET route in session.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldStore($request)) {
            $request->session()->put(LoginRedirectResolver::LAST_VISITED_URL_KEY, $request->fullUrl());
        }

        return $next($request);
    }

    /**
     * Determine whether the current request should be stored as the last visited URL.
     */
    protected function shouldStore(Request $request): bool
    {
        if (! $request->hasSession() || ! $request->user()) {
            return false;
        }

        if (! $request->isMethod('GET') || $request->expectsJson() || $request->ajax()) {
            return false;
        }

        if ($request->is('livewire/*')) {
            return false;
        }

        $route = $request->route();

        if (! $route) {
            return false;
        }

        $name = $route->getName();

        return ! in_array($name, [
            'login',
            'admin.login',
            'password.confirm',
            'password.confirmation',
            'pin.forgot',
        ], true);
    }
}

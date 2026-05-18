<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class LoginRedirectResolver
{
    public const LAST_VISITED_URL_KEY = 'auth.last_visited_url';

    /**
     * Resolve the best post-login destination for the authenticated user.
     */
    public function resolve(Request $request, ?User $user): string
    {
        $intended = $request->session()->pull('url.intended');
        $lastVisited = $request->session()->get(self::LAST_VISITED_URL_KEY);

        foreach ([$intended, $lastVisited] as $candidate) {
            if ($this->isValidDestination($candidate, $request, $user)) {
                return (string) $candidate;
            }
        }

        return $this->fallbackFor($user);
    }

    /**
     * Determine whether the given destination is valid for the authenticated user.
     */
    public function isValidDestination(?string $destination, Request $request, ?User $user): bool
    {
        if (! $user || ! filled($destination)) {
            return false;
        }

        if (! $this->isInternalUrl($destination, $request)) {
            return false;
        }

        $path = $this->extractPath($destination);

        if ($path === null || $this->isIgnoredPath($path)) {
            return false;
        }

        $route = $this->matchRoute($path);

        if (! $route || $this->isGuestRoute($route)) {
            return false;
        }

        return $this->routeAllowsUser($route, $user);
    }

    /**
     * Resolve the fallback route for the given user profile.
     */
    public function fallbackFor(?User $user): string
    {
        return match ($user?->profile) {
            'User' => route('user.home', absolute: false),
            'Doctor' => route('doctor.home', absolute: false),
            'Clerk' => route('clerk.dashboard', absolute: false),
            'Receptionist' => route('receptionist.appointments', absolute: false),
            default => route('dashboard', absolute: false),
        };
    }

    /**
     * Determine whether the route is only for guests.
     */
    protected function isGuestRoute(Route $route): bool
    {
        return in_array('guest', $route->gatherMiddleware(), true);
    }

    /**
     * Determine whether the matched route allows the given user profile.
     */
    protected function routeAllowsUser(Route $route, User $user): bool
    {
        $middleware = $route->gatherMiddleware();

        foreach ($middleware as $entry) {
            if ($entry === 'admin' && $user->profile !== 'Admin') {
                return false;
            }

            if ($entry === 'not-user' && $user->profile === 'User') {
                return false;
            }

            if (Str::startsWith($entry, 'profile:')) {
                $allowedProfiles = array_filter(explode(',', Str::after($entry, 'profile:')));

                if (! in_array($user->profile, $allowedProfiles, true)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Match the internal route represented by the given path.
     */
    protected function matchRoute(string $path): ?Route
    {
        try {
            return app('router')->getRoutes()->match(Request::create($path, 'GET'));
        } catch (HttpExceptionInterface) {
            return null;
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Determine whether the URL belongs to this application.
     */
    protected function isInternalUrl(string $destination, Request $request): bool
    {
        if (Str::startsWith($destination, '/')) {
            return true;
        }

        $host = parse_url($destination, PHP_URL_HOST);

        if (! $host) {
            return false;
        }

        $allowedHosts = array_filter([
            $request->getHost(),
            parse_url(config('app.url'), PHP_URL_HOST),
        ]);

        return in_array($host, Arr::flatten([$allowedHosts]), true);
    }

    /**
     * Extract the path and query string from the given destination.
     */
    protected function extractPath(string $destination): ?string
    {
        if (Str::startsWith($destination, '/')) {
            return $destination;
        }

        $path = parse_url($destination, PHP_URL_PATH);

        if (! is_string($path) || $path === '') {
            return null;
        }

        $query = parse_url($destination, PHP_URL_QUERY);

        return $query ? $path.'?'.$query : $path;
    }

    /**
     * Determine whether the given path should never be used as a post-login destination.
     */
    protected function isIgnoredPath(string $path): bool
    {
        $normalizedPath = '/'.ltrim(parse_url($path, PHP_URL_PATH) ?: $path, '/');

        return Str::startsWith($normalizedPath, '/livewire')
            || in_array($normalizedPath, [
                '/login',
                '/logout',
                '/admin/login',
                '/forgot-pin',
            ], true)
            || Str::startsWith($normalizedPath, '/pin/setup/')
            || Str::startsWith($normalizedPath, '/policy-registration/');
    }
}

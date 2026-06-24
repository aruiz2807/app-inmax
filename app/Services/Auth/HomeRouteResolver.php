<?php

namespace App\Services\Auth;

use App\Models\User;

class HomeRouteResolver
{
    /**
     * Resolve the preferred route name for the authenticated user.
     */
    public function routeNameFor(?User $user): string
    {
        if (! $user) {
            return 'login';
        }

        foreach (config("permissions.homes.{$user->profile}", []) as $candidate) {
            $permission = $candidate['permission'] ?? null;

            if ($permission === null || $user->hasPermission($permission)) {
                return $candidate['route'];
            }
        }

        return $this->fallbackRouteName($user);
    }

    /**
     * Resolve the preferred relative path for the authenticated user.
     */
    public function pathFor(?User $user): string
    {
        return route($this->routeNameFor($user), absolute: false);
    }

    /**
     * Safe fallback route when no configured home is currently accessible.
     */
    private function fallbackRouteName(?User $user): string
    {
        return match ($user?->profile) {
            'User' => 'user.home',
            default => 'profile.show',
        };
    }
}

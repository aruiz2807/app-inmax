<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     *
     * @param  \Illuminate\Http\Request  $request
     */
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        $user = $request->user();
        $target = $user?->profile === 'User'
            ? route('user.home', absolute: false)
            : route('dashboard', absolute: false);

        return redirect()->intended($target);
    }
}

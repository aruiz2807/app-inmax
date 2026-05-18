<?php

namespace App\Http\Responses;

use App\Services\Auth\LoginRedirectResolver;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function __construct(
        private readonly LoginRedirectResolver $redirectResolver,
    ) {}

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

        return redirect()->to(
            $this->redirectResolver->resolve($request, $request->user())
        );
    }
}

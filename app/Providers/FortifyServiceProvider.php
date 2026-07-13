<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Responses\LoginResponse;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LoginResponseContract::class, LoginResponse::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        // Old single-user login flow (commented for safety/reference)
        // Fortify::authenticateUsing(function (Request $request) {
        //     $request->validate([
        //         'phone' => ['required', 'digits:10'],
        //         'password' => ['required', 'digits:4'],
        //     ]);
        //
        //     $user = User::where('phone', (string) $request->string('phone'))->first();
        //
        //     if (! $user || ! filled($user->pin)) {
        //         return null;
        //     }
        //
        //     return Hash::check((string) $request->string('password'), $user->pin) ? $user : null;
        // });

        // New profile selector flow based on primary account's PIN access
        Fortify::authenticateUsing(function (Request $request) {
            $request->validate([
                'phone' => ['required', 'digits:10'],
                'password' => ['required', 'digits:4'],
            ]);

            $basePhone = (string) $request->string('phone');

            // 1. Find the Primary Account (either exact match or with suffix -01)
            $parentUser = User::where('phone', $basePhone)
                ->orWhere('phone', $basePhone . '-01')
                ->first();

            if (! $parentUser || ! filled($parentUser->pin)) {
                return null;
            }

            // 2. Validate the login attempt against the Primary Account's PIN
            if (! Hash::check((string) $request->string('password'), $parentUser->pin)) {
                return null;
            }

            // 3. Since PIN is valid, retrieve all matching profiles (with any suffix, e.g. -01, -02, etc.)
            $profiles = User::where('phone', 'like', $basePhone . '%')->get();

            // 4. If only one profile exists, log them in directly
            if ($profiles->count() === 1) {
                return $profiles->first();
            }

            // 5. Multiple profiles found: Store validated profile IDs in session & redirect to select screen
            session([
                'login.pending_profiles' => $profiles->pluck('id')->toArray(),
                'login.remember' => $request->boolean('remember'),
            ]);

            // Save the session data explicitly before terminating execution with exit;
            $request->session()->save();

            redirect()->route('login.profiles')->send();
            exit;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate($request->input('phone').'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('admin-login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input('email')).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}

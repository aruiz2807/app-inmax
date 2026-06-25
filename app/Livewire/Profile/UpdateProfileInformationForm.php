<?php

namespace App\Livewire\Profile;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Laravel\Jetstream\Http\Livewire\UpdateProfileInformationForm as BaseUpdateProfileInformationForm;

class UpdateProfileInformationForm extends BaseUpdateProfileInformationForm
{
    /**
     * Update profile and keep users on the layout route they came from.
     */
    public function updateProfileInformation(UpdatesUserProfileInformation $updater)
    {
        $this->resetErrorBag();

        $updater->update(
            Auth::user(),
            $this->photo
                ? array_merge($this->state, ['photo' => $this->photo])
                : $this->state
        );

        if (isset($this->photo)) {
            return redirect()->route($this->resolveProfileRoute());
        }

        $this->dispatch('saved');
        $this->dispatch('refresh-navigation-menu');
    }

    private function resolveProfileRoute(): string
    {
        $profile = Auth::user()?->profile;

        if ($profile === 'Doctor' && Route::has('doctor.my-profile')) {
            return 'doctor.my-profile';
        }

        if ($profile === 'User' && Route::has('user.my-profile')) {
            return 'user.my-profile';
        }

        return 'profile.show';
    }
}

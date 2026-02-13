<?php

namespace App\Livewire\Forms;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;

class UsersForm extends Form
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|email|max:255|unique:users,email')]
    public string $email = '';

    #[Validate('required|digits:10|unique:users,phone')]
    public string $phone = '';

    #[Validate('required|in:Admin,Doctor,Sales,User')]
    public string $profile = 'User';

    /**
     * Store the user in DB.
     */
    public function store(): User
    {
        $this->validate();

        return User::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'profile' => $this->profile,
            // Password remains as compatibility fallback, but pin is now used for login.
            'password' => Hash::make(Str::random(32)),
            'pin' => null,
            'pin_set_at' => null,
            'phone_verified_at' => null,
        ]);
    }

    /**
     * Set the user form state.
     */
    public function set(User $user): void
    {
        $this->name = $user->name;
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->profile = $user->profile;
    }

    /**
     * Update the user in DB.
     */
    public function update(int $userId): void
    {
        Validator::make([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'profile' => $this->profile,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['required', 'digits:10', Rule::unique('users', 'phone')->ignore($userId)],
            'profile' => ['required', Rule::in(['Admin', 'Doctor', 'Sales', 'User'])],
        ])->validate();

        $user = User::findOrFail($userId);

        $user->update([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'profile' => $this->profile,
        ]);
    }
}

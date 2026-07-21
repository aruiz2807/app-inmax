<?php

namespace App\Livewire\Forms;

use App\Models\User;
use Illuminate\Support\Facades\DB;
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

    public string $email = '';

    #[Validate('required|digits:10|unique:users,phone')]
    public string $phone = '';

    #[Validate('required|in:Admin,Doctor,Sales,Clerk,Receptionist,User')]
    public string $profile = 'User';

    public array $doctorIds = [];

    protected function rules()
    {
        return [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                $this->profile === 'User'
                    ? 'nullable'
                    : 'unique:users,contact_email',
            ],
        ];
    }

    /**
     * Store the user in DB.
     */
    public function store(): User
    {
        $this->validate();

        $user = User::create([
            'name' => $this->name,
            'email' => Str::lower($this->email),
            'contact_email' => Str::lower($this->email),
            'phone' => $this->phone,
            'profile' => $this->profile,
            // Password remains as compatibility fallback, but pin is now used for login.
            'password' => Hash::make(Str::random(32)),
            'pin' => null,
            'pin_set_at' => null,
            'phone_verified_at' => null,
        ]);

        $this->setPermissions($user);

        return $user;
    }

    private function setPermissions($user)
    {
        switch($user->profile)
        {
            case 'Clerk':
                DB::table('permission_user')->insert([
                    ['permission_id' => 21, 'user_id' => $user->id],
                ]);
                break;
            
            case 'Recepcionist':
                DB::table('permission_user')->insert([
                    ['permission_id' => 23, 'user_id' => $user->id],
                    ['permission_id' => 24, 'user_id' => $user->id],
                    ['permission_id' => 25, 'user_id' => $user->id],
                ]);
                break;

            case 'Doctor':
                DB::table('permission_user')->insert([
                    ['permission_id' => 26, 'user_id' => $user->id],
                    ['permission_id' => 27, 'user_id' => $user->id],
                ]);
                break;

            case 'Vendedor':
                DB::table('permission_user')->insert([
                    ['permission_id' => 2, 'user_id' => $user->id],
                ]);
                break;
        }
    }

    /**
     * Set the user form state.
     */
    public function set(User $user): void
    {
        $this->name = $user->name;
        $this->email = $user->contact_email ?? $user->email;
        $this->phone = $user->clean_phone;
        $this->profile = $user->profile;
        $this->doctorIds = $user->staffDoctors()->pluck('doctors.id')->toArray();
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
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                $this->profile === 'User'
                    ? 'nullable'
                    : Rule::unique('users', 'contact_email')->ignore($userId),
            ],
            'phone' => ['required', 'digits:10', Rule::unique('users', 'phone')->ignore($userId)],
            'profile' => ['required', Rule::in(['Admin', 'Doctor', 'Sales', 'Clerk', 'Receptionist', 'User'])],
        ])->validate();

        $user = User::findOrFail($userId);

        $uniqueEmail = $this->email;
        if ($user->is_dependent) {
            $parts = explode('-', $user->phone);
            $suffix = $parts[1] ?? '01';
            if (str_contains($this->email, '@')) {
                [$local, $domain] = explode('@', $this->email, 2);
                $uniqueEmail = "{$local}+{$suffix}@{$domain}";
            } else {
                $uniqueEmail = $this->email . '+' . $suffix;
            }
        }

        $user->update([
            'name' => $this->name,
            'email' => Str::lower($uniqueEmail),
            'contact_email' => Str::lower($this->email),
            'phone' => $this->phone,
            'profile' => $this->profile,
        ]);
    }
}

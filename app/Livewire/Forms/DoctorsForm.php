<?php

namespace App\Livewire\Forms;

use App\Models\User;
use App\Models\Doctor;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Validate;
use Livewire\Form;

class DoctorsForm extends Form
{
    #[Validate('required|string|max:255')]
    public $name = '';

    #[Validate('required|string|email|max:255|unique:users')]
    public $email = '';

    #[Validate('required|string|size:10|unique:users')]
    public $phone = '';

    #[Validate('required')]
    public $specialty = '';

    #[Validate('required|string|max:25')]
    public $license = '';

    #[Validate('required|string|max:100')]
    public $university = '';

    #[Validate('required')]
    public $address = '';

    /**
    * Store the doctor in the DB.
    */
    public function store(): User
    {
        $this->validate();

        $user = $this->createUser([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone
        ]);

        Doctor::create([
            'user_id' => $user->id,
            'specialty_id' => $this->specialty,
            'license' => $this->license,
            'university' => $this->university,
            'address' => $this->address,
        ]);

        return $user;
    }

    /**
     * Create the doctor's user.
     *
     * @param  array<string, string>  $input
     */
    public function createUser(array $input): User
    {
        return User::create([
            'name' => $input['name'],
            'profile' => 'Doctor',
            'email' => $input['email'],
            'phone' => $input['phone'],
            'password' => Hash::make(Str::random(32)),
            'pin' => null,
            'pin_set_at' => null,
            'phone_verified_at' => null,
        ]);
    }

    /**
    * Sets the service to edit.
    */
    public function set(Doctor $doctor)
    {
        $this->name = $doctor->name;
        $this->email = $doctor->email;
        $this->phone = $doctor->phone;
        $this->specialty = (string) $doctor->specialty_id;
        $this->license = $doctor->license;
        $this->university = $doctor->university;
        $this->address = $doctor->address;
    }

    /**
    * Updates the service in the DB.
    */
    public function update($doctorId)
    {
        $this->validate();

        $doctor = Doctor::find($doctorId);
        $user = User::find($doctor->user_id);

        $user->update([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
        ]);

        $doctor->update([
            'specialty' => $this->specialty,
            'license' => $this->license,
            'university' => $this->university,
            'address' => $this->address,
        ]);
    }
}

<?php

namespace App\Livewire\Forms;

use App\Enums\DoctorType;
use App\Models\User;
use App\Models\Doctor;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Validate;
use Livewire\Form;

class DoctorsForm extends Form
{
    #[Validate('required')]
    public $type;

    #[Validate('required|string|max:255')]
    public $name = '';

    #[Validate('required|string|email|max:255|unique:users')]
    public $email = '';

    #[Validate('required|string|size:10|unique:users')]
    public $phone = '';

    #[Validate('required')]
    public $specialty = '';

    #[Validate('required_if:type,' . DoctorType::Doctor->value . '|string|max:25')]
    public $license = '';

    #[Validate('required_if:type,' . DoctorType::Doctor->value . '|string|max:100')]
    public $university = '';

    #[Validate('required')]
    public $office = '';

    #[Validate('required')]
    public $address = '';

    #[Validate('required|max:2048')]
    public $maps_url = '';

    #[Validate('integer|min:0|max:100')]
    public $discount = 0;

    #[Validate('integer|min:0|max:100')]
    public $commission = 0;

    protected function rules()
    {
        return [
            'type' => ['required', new Enum(DoctorType::class)],
            'license' => [
                'nullable',
                'required_if:type,' . DoctorType::Doctor->value,
            ],
            'university' => [
                'nullable',
                'required_if:type,' . DoctorType::Doctor->value,
            ],
        ];
    }

    /**
    * Store the doctor in the DB.
    */
    public function store()
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
            'type' => $this->type,
            'license' => $this->license,
            'university' => $this->university,
            'office_id' => $this->office,
            'address' => $this->address,
            'maps_url' => $this->maps_url,
            'discount' => $this->discount,
            'commission' => $this->commission,
        ]);
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
            // for now, the phone number will be the user's password
            'password' => Hash::make($input['phone']),
        ]);
    }

    /**
    * Sets the doctor to edit.
    */
    public function set(Doctor $doctor)
    {
        $this->type = $doctor->type;
        $this->name = $doctor->user->name;
        $this->email = $doctor->user->email;
        $this->phone = $doctor->user->phone;
        $this->specialty = (string) $doctor->specialty_id;
        $this->license = $doctor->license;
        $this->university = $doctor->university;
        $this->office = (string) $doctor->office_id;
        $this->address = $doctor->address;
        $this->maps_url = $doctor->maps_url;
        $this->discount = $doctor->discount;
        $this->commission = $doctor->commission;
    }

    /**
    * Updates the doctor in the DB.
    */
    public function update($doctorId)
    {
        //$this->validate();

        $doctor = Doctor::find($doctorId);
        $user = User::find($doctor->user_id);

        $user->update([
            'name' => $this->name,
            //'email' => $this->email,
            //'phone' => $this->phone,
        ]);

        $doctor->update([
            'type' => $this->type,
            'specialty_id' => $this->specialty,
            'license' => $this->license,
            'university' => $this->university,
            'office_id' => $this->office,
            'address' => $this->address,
            'maps_url' => $this->maps_url,
            'discount' => $this->discount,
            'commission' => $this->commission,
        ]);
    }
}

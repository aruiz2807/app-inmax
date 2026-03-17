<?php

namespace App\Livewire\Forms;

use App\Models\Policy;
use App\Models\User;
use App\Services\Policies\IndividualPolicyRegistrationService;
use Livewire\Attributes\Validate;
use Livewire\Form;

class IndividualPolicyForm extends Form
{
    #[Validate('required|string|max:255')]
    public $name = '';

    #[Validate('required|string|email|max:255|unique:users')]
    public $email = '';

    #[Validate('required|string|size:10|unique:users')]
    public $phone = '';

    #[Validate('required|date|before_or_equal:today|after:1900-01-01')]
    public $birth = null;

    #[Validate('nullable|string|size:18')]
    public $curp = null;

    #[Validate('string|max:255')]
    public $passport = '';

    #[Validate('required')]
    public $plan = null;

    #[Validate('required|file|mimes:jpg,jpeg,png|max:2048')]
    public $attachment = null;

    #[Validate('nullable')]
    public $parent_policy = null;

    #[Validate('nullable')]
    public $sales_user = null;

    #[Validate('nullable|array')]
    public $insurance = [];

    public $photo = '/img/user.png';

    public bool $foreigner = false;

    public bool $addingMember = false;

    /**
    * Store the individual policy in the DB.
    */
    public function store(
        IndividualPolicyRegistrationService $registrationService,
        ?int $policyPreregistrationId = null
    ): Policy {
        $this->validate();

        $path = $this->attachment->store('profile-photos', 'public');

        return $registrationService->create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'birth' => $this->birth,
            'curp' => $this->curp,
            'passport' => $this->passport,
            'path' => $path,
            'plan_id' => (int) $this->plan,
            'sales_user_id' => $this->sales_user ? (int) $this->sales_user : null,
            'parent_policy_id' => $this->parent_policy ? (int) $this->parent_policy : null,
            'insurance' => $this->insurance,
            'adding_member' => $this->addingMember,
            'policy_preregistration_id' => $policyPreregistrationId,
        ]);
    }

    /**
    * Sets the policy to edit.
    */
    public function set(Policy $policy)
    {
        $this->name = $policy->user->name;
        $this->email = $policy->user->email;
        $this->phone = $policy->user->phone;
        $this->birth = $policy->user->birth_date->format('Y-m-d');
        $this->curp = $policy->user->curp;
        $this->passport = $policy->user->passport;
        $this->plan = (string) $policy->plan_id;
        $this->sales_user = (string) $policy->sales_user_id;
        $this->parent_policy = (string) $policy->parent_policy_id;
        $this->insurance = $policy->insurance;
        $this->photo = $policy->user->photo_url;

        if($this->passport)
        {
            $this->foreigner = true;
        }
    }

    /**
    * Sets the policy to add member.
    */
    public function member(Policy $policy)
    {
        $this->plan = (string) $policy->plan_id;
        $this->parent_policy = (string) $policy->id;
        $this->addingMember = true;
    }

    /**
    * Updates the policy in the DB.
    */
    public function update($policyId)
    {
        // $this->validate();

        $policy = Policy::find($policyId);
        $user = User::find($policy->user_id);
        $path = $this->attachment->store('profile-photos', 'public');

        $user->update([
            'name' => $this->name,
            //'email' => $this->email,
            //'phone' => $this->phone,
            'profile_photo_path' => $path,
        ]);

        $policy->update([
            'insurance' => $this->insurance
        ]);
    }

}

<?php

namespace App\Livewire\Forms;

use App\Models\Policy;
use App\Services\Policies\GroupPolicyRegistrationService;
use Livewire\Attributes\Validate;
use Livewire\Form;

class GroupPolicyForm extends Form
{
    #[Validate('required|string|max:100')]
    public $company = '';

    #[Validate('required')]
    public $type = 'PF';

    #[Validate('required|string|max:100')]
    public $legal_name = '';

    #[Validate('required|string|min:12|max:13')]
    public $rfc = '';

    #[Validate('required|string|max:255')]
    public $name = '';

    #[Validate('required|string|email|max:255|unique:users')]
    public $email = '';

    #[Validate('required|string|size:10|unique:users')]
    public $phone = '';

    #[Validate('required|date|before_or_equal:today|after:1900-01-01')]
    public $birth = null;

    #[Validate('string|size:18')]
    public $curp = '';

    #[Validate('string|max:255')]
    public $passport = '';

    #[Validate('required')]
    public $plan = null;

    #[Validate('nullable')]
    public $sales_user = null;

    #[Validate('nullable|array')]
    public $insurance = [];

    #[Validate('required|numeric|min:1|max:99')]
    public $members = 0;

    public bool $foreigner = false;


    /**
    * Store the group policy in the DB.
    */
    public function store(
        GroupPolicyRegistrationService $registrationService,
        ?int $policyPreregistrationId = null
    ): Policy
    {
        $this->validate();

        return $registrationService->create([
            'company' => $this->company,
            'type' => $this->type,
            'legal_name' => $this->legal_name,
            'rfc' => $this->rfc,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'birth' => $this->birth,
            'curp' => $this->curp,
            'passport' => $this->passport,
            'plan_id' => (int) $this->plan,
            'sales_user_id' => $this->sales_user ? (int) $this->sales_user : null,
            'insurance' => $this->insurance,
            'members' => (int) $this->members,
            'policy_preregistration_id' => $policyPreregistrationId,
        ]);
    }

     /**
    * Sets the policy to edit.
    */
    public function set(Policy $policy)
    {
        $this->company = $policy->user->company->name;
        $this->type = $policy->user->company->type;
        $this->legal_name = $policy->user->company->legal_name;
        $this->rfc = $policy->user->company->rfc;

        $this->name = $policy->user->name;
        $this->email = $policy->user->email;
        $this->phone = $policy->user->phone;
        $this->birth = $policy->user->birth_date->format('Y-m-d');
        $this->curp = $policy->user->curp;
        $this->passport = $policy->user->passport;
        $this->plan = (string) $policy->plan_id;
        $this->sales_user = (string) $policy->sales_user_id;
        $this->insurance = $policy->insurance;
        $this->members = $policy->members;

        if($this->passport)
        {
            $this->foreigner = true;
        }
    }

    /**
    * Updates the policy in the DB.
    */
    public function update($policyId)
    {
        $this->validate();

        $policy = Policy::find($policyId);
        $user = User::find($policy->user_id);
        $company = $user->company;

        $company->update([
            'name' => $this->company,
            'type' => $this->type,
            'legal_name' => $this->legal_name,
            'rfc' => $this->rfc,
        ]);

        $user->update([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
        ]);

        $policy->update([
            'insurance' => $this->insurance
        ]);
    }

}

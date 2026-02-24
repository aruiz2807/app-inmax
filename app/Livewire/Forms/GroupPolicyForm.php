<?php

namespace App\Livewire\Forms;

use App\Models\User;
use App\Models\Company;
use App\Models\PlanBenefit;
use App\Models\PolicyService;
use App\Models\Policy;

use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Validate;
use Livewire\Form;
use Carbon\Carbon;

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
    public function store()
    {
        $this->validate();

        $company = $this->createCompany([
            'company' => $this->company,
            'type' => $this->type,
            'legal_name' => $this->legal_name,
            'rfc' => $this->rfc,
        ]);

        $user = $this->createUser([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'birth' => $this->birth,
            'curp' => $this->curp,
            'passport' => $this->passport,
            'company' => $company->id,
        ]);

        $policy = Policy::create([
            'user_id' => $user->id,
            'sales_user_id' => $this->sales_user,
            'plan_id' => $this->plan,
            'number' => $this->getPolicyNumber(),
            'type' => 'Group',
            'members' => $this->members,
            'insurance' => $this->insurance,
        ]);

        $benefits = PlanBenefit::where('plan_id', $this->plan)->orderBy('service_id')->get();

        foreach($benefits as $benefit)
        {
            PolicyService::create([
                'policy_id' => $policy->id,
                'service_id' => $benefit->service_id,
                'included' => round(($benefit->events * $this->members) / 2),
            ]);
        }
    }

    /**
     * Create the user's company.
     *
     * @param  array<string, string>  $input
     */
    public function createCompany(array $input): Company
    {
        return Company::create([
            'name' => $input['company'],
            'type' => $input['type'],
            'legal_name' => $input['legal_name'],
            'rfc' => $input['rfc'],
        ]);
    }

    /**
     * Create the user's policy.
     *
     * @param  array<string, string>  $input
     */
    public function createUser(array $input): User
    {
        return User::create([
            'name' => $input['name'],
            'profile' => 'User',
            'email' => $input['email'],
            'phone' => $input['phone'],
            'birth_date' => $input['birth'],
            'curp' => $input['curp'],
            'passport' => $input['passport'],
            'company_id' => $input['company'],
            // for now, the phone number will be the user's password
            'password' => Hash::make($input['phone']),
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

    /**
     * Determines policy number.
     */
    public function getPolicyNumber(): String
    {
        $year = Carbon::now()->year;
        $shortYear = Carbon::now()->format('y');
        $next = Policy::where('plan_id', $this->plan)->whereYear('created_at', $year)->count() + 1;
        $number = str_pad($next, 5, '0', STR_PAD_LEFT);
        $plan = str_pad($this->plan, 2, '0', STR_PAD_LEFT);

        return "INX{$shortYear}GR{$plan}-{$number}";
    }
}

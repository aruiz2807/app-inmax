<?php

namespace App\Livewire\Forms;

use App\Services\Auth\PinSetupTokenService;
use App\Services\WhatsApp\WhatsAppCloudApiService;
use App\Models\User;
use App\Models\Policy;
use App\Models\PlanBenefit;
use App\Models\PolicyService;
use Illuminate\Support\Facades\Hash;

use Livewire\Attributes\Validate;
use Livewire\Form;
use Carbon\Carbon;

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
    public function store()
    {
        $this->validate();

        $path = $this->attachment->store('profile-photos', 'public');

        $user = $this->createUser([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'birth' => $this->birth,
            'curp' => $this->curp,
            'passport' => $this->passport,
            'path' => $path,
        ]);

        if($this->addingMember)
        {
            $parent_policy = Policy::find($this->parent_policy);
            $user->update([
                'company_id' => $parent_policy->user->company_id,
            ]);
        }

        $policy = Policy::create([
            'user_id' => $user->id,
            'sales_user_id' => $this->sales_user,
            'plan_id' => $this->plan,
            'parent_policy_id' => $this->parent_policy ?: null,
            'number' => $this->getPolicyNumber(),
            'type' => $this->addingMember ? 'Member' : 'Individual',
            'insurance' => $this->insurance,
        ]);

        if(!$this->addingMember)
        {
            $benefits = PlanBenefit::where('plan_id', $this->plan)->orderBy('service_id')->get();

            foreach($benefits as $benefit)
            {
                PolicyService::create([
                    'policy_id' => $policy->id,
                    'service_id' => $benefit->service_id,
                    'included' => $benefit->events
                ]);
            }
        }

        $whatsappService = new PinSetupTokenService(new WhatsAppCloudApiService());
        $whatsappService->generateSetupLink($user);
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
            // for now, the phone number will be the user's password
            'password' => Hash::make($input['phone']),
            'profile_photo_path' => $input['path'],
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

    /**
     * Determines policy number.
     */
    public function getPolicyNumber(): String
    {
        if($this->parent_policy)
        {
            $parentNumber = Policy::where('id', $this->parent_policy)->value('number');
            $next = Policy::where('parent_policy_id', $this->parent_policy)->count() + 1;
            $suffix = str_pad($next, 2, '0', STR_PAD_LEFT);

            return "{$parentNumber}-{$suffix}";
        }

        $year = Carbon::now()->year;
        $shortYear = Carbon::now()->format('y');
        $next = Policy::where('plan_id', $this->plan)->whereYear('created_at', $year)->count() + 1;
        $number = str_pad($next, 5, '0', STR_PAD_LEFT);
        $plan = str_pad($this->plan, 2, '0', STR_PAD_LEFT);

        return "INX{$shortYear}IN{$plan}-{$number}";
    }
}

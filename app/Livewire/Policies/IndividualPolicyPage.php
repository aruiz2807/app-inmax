<?php

namespace App\Livewire\Policies;



use App\Livewire\Forms\IndividualPolicyForm;
use App\Models\Plan;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Carbon\Carbon;

class IndividualPolicyPage extends Component
{
    public IndividualPolicyForm $form;
    public ?int $policyId = null;
    public $plans = [];
    public $policies = [];
    public $sales_agents = [];
    public $member = false;

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.policies.individual-policy-page');
    }

    public function mount($policyId, $newMember)
    {
        if($policyId && !$newMember)
        {
            $this->policyId = $policyId;
            $this->form->set(Policy::find($policyId));
        }

        if(!$newMember)
        {
            $this->plans = Plan::orderBy('name')->where([
                ['type', 'Individual'],
                ['status', 'Active'],
            ])->get();

            $this->policies = Policy::with('user:id,name')
                ->whereNull('parent_policy_id')
                ->where('id', '!=', $policyId) // exclude this policy
                ->whereHas('plan', function ($query) {
                    $query->where('type', 'individual'); // filter only 'individual' plans
            })->get();
        }
        else
        {
            $this->plans = Plan::orderBy('name')->where([
                ['type', 'Group'],
                ['status', 'Active'],
            ])->get();

            $this->policies = Policy::where('id', $policyId)
                ->with(['user:id,name,company_id', 'user.company:id,name'])
                ->get();

            $this->form->member(Policy::find($policyId));
            $this->member = $newMember;
        }

        $this->form->sales_user = Auth::user()?->profile === 'Sales' ? Auth::user()->id : null;

        $this->sales_agents = User::where('profile', 'Sales')
            ->select('id', 'name')
            ->get();
    }

    public function save()
    {
        if($this->policyId and !$this->member)
        {
            $this->form->update($this->policyId);
        }
        else
        {
            $this->form->store();
        }

        // Show success toast
        $this->dispatch('notify',
            type: 'success',
            content:'Poliza almacenada exitosamente!',
            duration: 4000
        );

        //close modal
        $this->dispatch('close-policy-modal');

        //refresh table data
        $this->dispatch('pg:eventRefresh-policiesTable');

        //clear form
        $this->resetForm();
    }

    #[Computed]
    public function age()
    {
        if(!$this->form->birth)
        {
            return null;
        }

        try
        {
            return Carbon::parse($this->form->birth)->age;
        }
        catch (\Exception $e)
        {
            return null;
        }
    }

    public function resetForm()
    {
        $this->form->reset();
        $this->member = false;
        $this->policyId = null;
    }
}

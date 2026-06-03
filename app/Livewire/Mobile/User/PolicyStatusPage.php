<?php

namespace App\Livewire\Mobile\User;

use App\Models\PolicyService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

class PolicyStatusPage extends Component
{
    public $percentage;
    public $total_included;
    public $total_used;
    public $total_extra;
    public $services = [];
    public $policy_type;
    public $icon;

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.user.policy-status-page');
    }

    public function mount()
    {
        $user = Auth::user();

        $policyId = $user->policy->type === 'Member' 
            ? $user->policy->parent_policy_id 
            : $user->policy->id;

        $this->services = PolicyService::with([
                'service', 
                'coupon'
            ])
            ->where('policy_id', $policyId)
            ->get();

        $sum = PolicyService::where('policy_id', $policyId)
            ->selectRaw('SUM(included) as total_included, SUM(used) as total_used, SUM(extra) as total_extra')
            ->first();

        $this->policy_type = $user->policy->type === 'Individual' ? 'Individual' : 'Colectiva';
        $this->icon = $user->policy->type === 'Individual' ? 'user' : 'user-group';
        $this->total_included = $sum->total_included ?? 0;
        $this->total_used = $sum->total_used ?? 0;
        $this->total_extra = $sum->total_extra ?? 0;
        $this->percentage = $this->total_included > 0 ? round(($this->total_used / $this->total_included) * 100) : 0;
    }
}

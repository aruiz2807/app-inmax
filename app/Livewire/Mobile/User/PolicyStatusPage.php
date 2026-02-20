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

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.user.policy-status-page');
    }

    public function mount()
    {
        $user = Auth::user();
        $this->services = PolicyService::where('policy_id', $user->policy->id)->get();

        $sum = PolicyService::where('policy_id', $user->policy->id)
            ->selectRaw('SUM(included) as total_included, SUM(used) as total_used, SUM(extra) as total_extra')
            ->first();

        $this->total_included = $sum->total_included;
        $this->total_used = $sum->total_used;
        $this->total_extra = $sum->total_extra;
        $this->percentage = $this->total_included > 0 ? round(($this->total_used / $this->total_included) * 100) : 0;
    }
}

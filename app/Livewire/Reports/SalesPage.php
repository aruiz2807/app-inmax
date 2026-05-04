<?php

namespace App\Livewire\Reports;

use App\Models\Policy;
use App\Models\User;
use App\Models\Parameter;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Carbon;

class SalesPage extends Component
{
    public $year;
    public $month;
    public $sales_user_id;
    public $selectedPolicy;

    public function mount()
    {
        $this->year = Carbon::now()->year;
        $this->month = Carbon::now()->month;
    }

    public function getMonthsProperty()
    {
        return [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
    }

    public function getYearsProperty()
    {
        $currentYear = Carbon::now()->year;
        return range($currentYear, $currentYear - 2);
    }

    public function getSalesUsersProperty()
    {
        return User::where('profile', 'Sales')->get();
    }

    public function showDetails($policyId)
    {
        $this->selectedPolicy = Policy::with(['user', 'sales_user', 'plan'])->find($policyId);
        $this->dispatch('open-modal');
    }

    public function getPoliciesProperty()
    {
        $query = Policy::with(['user', 'sales_user', 'plan'])
            ->where('status', 'Active')
            ->whereNotNull('payment_method');

        if ($this->year) {
            $query->whereYear('start_date', $this->year);
        }

        if ($this->month) {
            $query->whereMonth('start_date', $this->month);
        }

        if ($this->sales_user_id) {
            $query->where('sales_user_id', $this->sales_user_id);
        }

        return $query->orderBy('start_date', 'desc')->get();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $policies = $this->policies;
        $commissionPercentage = Parameter::where('type', 'CV')->where('key', 'Comision')->value('value') ?? 0;

        $groupedPolicies = $policies->groupBy(function($policy) {
            return $policy->sales_user->name ?? 'Sin vendedor';
        });

        // Calculate commissions for each policy
        $policies->each(function($policy) use ($commissionPercentage) {
            $policy->plan_price = $policy->plan->price ?? 0;
            $policy->calculated_commission = $policy->plan_price * ($commissionPercentage / 100);
        });

        $totals = [
            'price' => $policies->sum('plan_price'),
            'commission' => $policies->sum('calculated_commission'),
        ];

        return view('livewire.reports.sales-page', [
            'groupedPolicies' => $groupedPolicies,
            'salesUsers' => $this->salesUsers,
            'totals' => $totals,
        ]);
    }
}

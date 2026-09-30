<?php

namespace App\Livewire\Home;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Parameter;
use App\Services\Reports\CommissionSummary;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

class DashboardPage extends Component
{
    #[Layout('layouts.app')]
    public function render()
    {
        $today = Carbon::today();
        $mgSpecialty = Parameter::where('type', 'MG')
            ->where('key', 'Especialidad')
            ->first();

        $upcomingAppointments = Appointment::query()
            ->with(['user:id,name', 'doctor.user:id,name', 'office:id,name'])
            ->where('status', AppointmentStatus::BOOKED->value)
            ->whereBetween('date', [$today, $today->copy()->addDays(6)])
            ->orderBy('date')
            ->orderBy('time')
            ->get();

        $commissionAppointments = Appointment::query()
            ->with('doctor:id,specialty_id')
            ->where('status', AppointmentStatus::COMPLETED->value)
            ->whereYear('date', $today->year)
            ->whereMonth('date', $today->month)
            ->get();

        return view('livewire.home.dashboard-page', [
            'upcomingAppointments' => $upcomingAppointments,
            'totals' => CommissionSummary::calculate(
                $commissionAppointments,
                $mgSpecialty ? (int) $mgSpecialty->value : null
            ),
        ]);
    }
}

<?php

namespace App\Livewire\Reports;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Enums\AppointmentStatus;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Illuminate\Support\Carbon;

class CommissionsPage extends Component
{
    public $year;
    public $month;
    public $doctor_id;
    public $selectedAppointment;

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

    public function showDetails($appointmentId)
    {
        $this->selectedAppointment = Appointment::with(['user', 'doctor.user', 'doctor.specialty', 'services.service', 'office'])->find($appointmentId);
        $this->dispatch('open-modal');
    }

    public function getDoctorsProperty()
    {
        return Doctor::with('user')->get();
    }

    public function getAppointmentsProperty()
    {
        $query = Appointment::with(['user', 'doctor.user'])
            ->where('status', AppointmentStatus::COMPLETED);

        if ($this->year) {
            $query->whereYear('date', $this->year);
        }

        if ($this->month) {
            $query->whereMonth('date', $this->month);
        }

        if ($this->doctor_id) {
            $query->where('doctor_id', $this->doctor_id);
        }

        return $query->orderBy('date', 'desc')->get();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $appointments = $this->appointments;

        $groupedAppointments = $appointments->groupBy(function($appointment) {
            return $appointment->doctor->user->name;
        });

        $totals = [
            'subtotal' => $appointments->sum('subtotal'),
            'coupon_discount' => $appointments->sum('coupon_discount'),
            'user_payment' => $appointments->sum('user_payment'),
            'commission' => $appointments->sum('commission'),
            'total' => $appointments->sum('total'),
        ];

        return view('livewire.reports.commissions-page', [
            'groupedAppointments' => $groupedAppointments,
            'doctors' => $this->doctors,
            'totals' => $totals,
        ]);
    }
}
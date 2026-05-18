<?php

namespace App\Livewire\Reports;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Parameter;
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
        $query = Appointment::with(['user', 'doctor.user', 'doctor.specialty'])
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

    public function getMgSpecialtyIdProperty()
    {
        $param = Parameter::where('type', 'MG')->where('key', 'Especialidad')->first();
        return $param ? (int) $param->value : null;
    }

    #[Layout('layouts.app')]
    public function render()
    {
        $appointments = $this->appointments;
        $mgSpecialtyId = $this->mgSpecialtyId;

        $groupedAppointments = $appointments->groupBy(function($appointment) {
            return $appointment->doctor->user->name;
        });

        // We need to calculate totals considering the visual inversion for MG doctors
        $subtotal = 0;
        $coupon_discount = 0;
        $user_payment = 0;
        $commission = 0;
        $total = 0;

        foreach ($appointments as $app) {
            $subtotal += $app->subtotal;
            $coupon_discount += $app->coupon_discount;
            $user_payment += $app->user_payment;

            if ($app->doctor->specialty_id === $mgSpecialtyId) {
                // For MG doctors, commission shows inverted total, and total shows 0
                $commission += -$app->total;
                $total += 0;
            } else {
                $commission += $app->commission;
                $total += $app->total;
            }
        }

        $totals = [
            'subtotal' => $subtotal,
            'coupon_discount' => $coupon_discount,
            'user_payment' => $user_payment,
            'commission' => $commission,
            'total' => $total,
            'mg_specialty_id' => $mgSpecialtyId,
        ];

        return view('livewire.reports.commissions-page', [
            'groupedAppointments' => $groupedAppointments,
            'doctors' => $this->doctors,
            'totals' => $totals,
        ]);
    }
}
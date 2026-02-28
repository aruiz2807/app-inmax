<?php

namespace App\Livewire\Appointments;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\Policy;
use App\Models\Service;
use App\Models\User;
use App\Models\PolicyService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

class AppointmentFormPage extends Component
{
    public $appointment;
    public $selectedDate;
    public $selectedTime;
    public $selectedService;
    public $selectedUser;
    public $selectedDoctor;
    public $availableDates = [];
    public $availableHours = [];
    public $isIncluded;
    public $services;
    public $doctors;
    public $policies;
    public $user;
    public $doctor;

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.appointments.appointment-form-page');
    }

    public function mount($appointmentId)
    {
        $this->services = Service::where('status', 'Active')->get();
        $this->policies = Policy::where('status', 'Active')->get();
        $this->doctors = Doctor::where('status', 'Active')->get();
        $date = Carbon::now();
        $count = 0;
        $maxDays = 15;

        while ($count < $maxDays)
        {
            $date->addDay();

            if (!$date->isSunday())
            {
                $this->availableDates[] = [
                    'id'    => $date->format('Y-m-d'),
                    'day'   => $date->isoFormat('ddd'),
                    'num'   => $date->format('d'),
                    'month' => $date->isoFormat('MMM'),
                ];
                $count++;
            }
        }

        $this->selectedDate = $this->availableDates[0]['id'];
        $this->fetchAvailableSlots();
        $this->set($appointmentId);
    }

    public function updatedSelectedUser($value)
    {
        $this->selectedUser = $value;
        $this->user = User::find($value);
    }

    public function updatedSelectedService($value)
    {
        $this->selectedService = $value;
        $this->doctors = Doctor::where('specialty_id', $value)->get();
    }

    public function updatedSelectedDoctor($value)
    {
        $this->selectedDoctor = $value;
        $this->doctor = Doctor::find($value);

        if($this->user->policy->type === 'Member')
        {
            $policy_id  = $this->user->policy->parent_policy_id;
        }
        else
        {
            $policy_id  = $this->user->policy->id;
        }

        $this->isIncluded = PolicyService::query()
            ->where('policy_id', $policy_id)
            ->where('service_id', $this->selectedService)
            ->whereColumn('used', '<', 'included')
            ->exists();
    }

    public function updatedSelectedDate($value)
    {
        $this->selectedDate = $value;
        $this->fetchAvailableSlots();
    }

    public function fetchAvailableSlots()
    {
        $this->availableHours = [];

        $usedSlots = Appointment::whereDate('date', $this->selectedDate)
            ->pluck('time')
            ->mapWithKeys(fn ($time) => [
                Carbon::parse($time)->format('H:i') => true
            ])
            ->toArray();

        // Should check database
        $availableSlots = [
            '09:00 AM', '10:00 AM', '11:00 AM', '12:00 PM', '01:00 PM', '02:00 PM', '03:00 PM', '04:00 PM', '05:00 PM', '06:00 PM', '07:00 PM'
        ];

        foreach ($availableSlots as $slot)
        {
            $normalized = Carbon::createFromFormat('h:i A', $slot)->format('H:i');

            if (!isset($usedSlots[$normalized]))
            {
                $this->availableHours[] = [
                    'id'   => $normalized, // 14:00 (for DB usage later)
                    'time' => $slot,       // 02:00 PM (for UI)
                ];
            }
        }

        $this->selectedTime = $this->availableHours[0]['id'];
    }

    public function clear()
    {
        $this->user = null;
        $this->doctor = null;
        $this->selectedDate = $this->availableDates[0]['id'];
        $this->selectedTime = $this->availableHours[0]['id'];
        $this->selectedService = null;
        $this->selectedUser = null;
        $this->selectedDoctor = null;
        $this->isIncluded = null;
    }

    public function schedule()
    {
        if($this->appointment)
        {
            $this->appointment->update([
                'doctor_id' => $this->selectedDoctor,
                'date' => $this->selectedDate,
                'time' => $this->selectedTime,
                'covered' => $this->isIncluded,
            ]);
        }
        else
        {
            Appointment::create([
                'user_id' => $this->selectedUser,
                'doctor_id' => $this->selectedDoctor,
                'date' => $this->selectedDate,
                'time' => $this->selectedTime,
                'covered' => $this->isIncluded,
            ]);
        }

        // Show success toast
        $this->dispatch('notify',
            type: 'success',
            content:'Cita almacenada exitosamente!',
            duration: 4000
        );

        //close modal
        $this->dispatch('close-appointment-modal');

        //refresh table data
        $this->dispatch('pg:eventRefresh-appointmentsTable');
    }

    /**
    * Sets the appontment to edit.
    */
    public function set($appointmentId)
    {
        if($appointmentId)
        {
            $this->appointment = Appointment::findOrFail($appointmentId);

            $this->selectedUser = (string) $this->appointment->user->id;
            $this->user = User::find($this->selectedUser);
            $this->selectedService = (string) $this->appointment->doctor->specialty->service_id;
            $this->selectedDoctor = (string) $this->appointment->doctor_id;
            $this->doctor = Doctor::find($this->selectedDoctor);
            $this->selectedDate = $this->appointment->date->format('Y-m-d');
            $this->selectedTime = $this->appointment->time->format('H:i');
            $this->isIncluded = $this->appointment->covered;
        }
    }
}

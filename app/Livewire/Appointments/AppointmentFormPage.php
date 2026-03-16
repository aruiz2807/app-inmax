<?php

namespace App\Livewire\Appointments;

use App\Models\Appointment;
use App\Models\AppointmentService;
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

    public $selectedUser;
    public $selectedDoctor;
    public $selectedServices = [];

    public $doctors;
    public $policies;
    public $isIncluded = 1;

    public $user;
    public $doctor;
    public $servicesData = [];

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.appointments.appointment-form-page');
    }

    public function mount($appointmentId)
    {
        $this->policies = Policy::where('status', 'Active')->get();
        $this->doctors = Doctor::where('status', 'Active')->get();

        if ($appointmentId)
        {
            $this->set($appointmentId);
        }
        else
        {
            $this->selectedDate = now()->addDay()->format('Y-m-d');
            $this->selectedTime = '09:00';
        }
    }

    public function updatedSelectedUser($value)
    {
        $this->user = User::find($value);
    }

    public function updatedSelectedDoctor($value)
    {
        $this->doctor = Doctor::find($value);
    }

    public function updatedSelectedServices($value)
    {
        $policyId = $this->user->policy->type === 'Member' ? $this->user->policy->parent_policy_id : $this->user->policy->id;
        $includedServices = PolicyService::query()
            ->where('policy_id', $policyId)
            ->whereColumn('used', '<', 'included')
            ->pluck('service_id')
            ->toArray();

        $services = Service::whereIn('id', $this->selectedServices)
            ->get()
            ->keyBy('id');

        $this->servicesData = collect($this->selectedServices)->map(function ($serviceId) use ($services, $includedServices)
        {
            $service = $services->get($serviceId);
            return [
                'id' => $serviceId,
                'name' => $service?->name,
                'included' => in_array($serviceId, $includedServices),
            ];
        })->values()->toArray();
    }

    public function clear()
    {
        $this->reset([
            'selectedUser',
            'selectedDoctor',
            'selectedServices',
            'selectedDate',
            'selectedTime',
        ]);
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
            $appointment = Appointment::create([
                'user_id' => $this->selectedUser,
                'doctor_id' => $this->selectedDoctor,
                'date' => $this->selectedDate,
                'time' => $this->selectedTime,
                'covered' => $this->isIncluded,
            ]);

            foreach($this->servicesData as $service)
            {
                AppointmentService::create([
                    'appointment_id' => $appointment->id,
                    'service_id' => $service['id'],
                    'covered' => $service['included'],
                ]);
            }
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
        $this->appointment = Appointment::findOrFail($appointmentId);
        $appointmentServices = AppointmentService::where('appointment_id', $this->appointment->id)->get();

        $this->selectedUser = (string) $this->appointment->user->id;
        $this->user = User::find($this->selectedUser);

        $this->selectedDoctor = (string) $this->appointment->doctor_id;
        $this->doctor = Doctor::find($this->selectedDoctor);

        $this->selectedDate = $this->appointment->date->format('Y-m-d');
        $this->selectedTime = $this->appointment->time->format('H:i');
        $this->isIncluded = $this->appointment->covered;

        $this->selectedServices = $appointmentServices->pluck('id')->toArray();
        $this->servicesData = $appointmentServices->map(fn ($appointmentService) => [
            'id' => $appointmentService->service_id,
            'name' => $appointmentService->service->name,
            'included' => $appointmentService->covered,
        ])->values()->toArray();
    }

    public function getServicesProperty()
    {
        if (!$this->selectedDoctor) {
            return collect();
        }

        return Doctor::with('specialty.services')->find($this->selectedDoctor)?->specialty?->services ?? collect();
    }

    public function getServicesDataProperty()
    {
        if (!$this->selectedUser || !$this->selectedServices) {
            return [];
        }

        $user = User::with('policy')->find($this->selectedUser);

        $policyId = $user->policy->type === 'Member'
            ? $user->policy->parent_policy_id
            : $user->policy->id;

        $included = PolicyService::where('policy_id', $policyId)
            ->whereColumn('used', '<', 'included')
            ->pluck('service_id')
            ->toArray();

        $services = Service::whereIn('id', $this->selectedServices)
            ->get();

        return $services->map(fn ($service) => [
            'id' => $service->id,
            'name' => $service->name,
            'included' => in_array($service->id, $included)
        ])->toArray();
    }

    public function getAvailableDatesProperty()
    {
        $dates = [];
        $date = Carbon::now();

        while (count($dates) < 15) {
            $date->addDay();

            if (!$date->isSunday()) {
                $dates[] = [
                    'id'    => $date->format('Y-m-d'),
                    'day'   => $date->isoFormat('ddd'),
                    'num'   => $date->format('d'),
                    'month' => $date->isoFormat('MMM'),
                ];
            }
        }

        return $dates;
    }

    public function getAvailableHoursProperty()
    {
        if (!$this->selectedDate) {
            return [];
        }

        $usedSlots = Appointment::whereDate('date', $this->selectedDate)
            ->pluck('time')
            ->map(fn ($time) => Carbon::parse($time)->format('H:i'))
            ->toArray();

        $slots = [
            '09:00 AM','10:00 AM','11:00 AM','12:00 PM',
            '01:00 PM','02:00 PM','03:00 PM','04:00 PM',
            '05:00 PM','06:00 PM','07:00 PM'
        ];

        return collect($slots)
            ->map(function ($slot) use ($usedSlots) {

                $normalized = Carbon::createFromFormat('h:i A', $slot)->format('H:i');

                if (in_array($normalized, $usedSlots)) {
                    return null;
                }

                return [
                    'id' => $normalized,
                    'time' => $slot,
                ];

            })
            ->filter()
            ->values()
            ->toArray();
    }
}

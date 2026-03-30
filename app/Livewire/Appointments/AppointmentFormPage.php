<?php

namespace App\Livewire\Appointments;

use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Doctor;
use App\Models\Policy;
use App\Models\Service;
use App\Models\User;
use App\Models\PolicyService;
use App\Services\Appointments\AppointmentRequestNotificationService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class AppointmentFormPage extends Component
{
    public $appointment;

    public $selectedDate;
    public $selectedTime;

    public $selectedUser;
    public $selectedDoctor;
    public $selectedOffice;
    public $selectedServices = [];

    public $user;
    public $doctor;

    public $doctors;
    public $policies;
    public $offices;
    public $services;
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
            $this->selectedDate = $this->availableDates[0]['id'];
            $this->selectedTime = $this->availableHours[0]['id'];
        }
    }

    public function updatedSelectedUser($value)
    {
        $this->user = User::find($value);
    }

    public function updatedSelectedDoctor($value)
    {
        $this->reset([
            'services',
            'selectedServices',
            'servicesData',
        ]);

        $this->doctor = Doctor::find($value);
        $this->offices = $this->doctor->offices;
        $this->services = $this->doctor->specialty->services;
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
            'selectedOffice',
            'selectedServices',
            'selectedDate',
            'selectedTime',
            'servicesData',
            'offices',
        ]);

        $this->selectedDate = $this->availableDates[0]['id'];
        $this->selectedTime = $this->availableHours[0]['id'];
    }

    public function schedule(AppointmentRequestNotificationService $appointmentNotificationService)
    {
        $notificationResult = null;

        if($this->appointment)
        {
            $this->appointment->update([
                'date' => $this->selectedDate,
                'time' => $this->selectedTime,
            ]);
        }
        else
        {
            $doctor = Doctor::find($this->selectedDoctor);
            $appointment = Appointment::create([
                'user_id' => $this->selectedUser,
                'doctor_id' => $this->selectedDoctor,
                'office_id' => $this->selectedOffice,
                'requested_by_user_id' => Auth::user()->id,
                'date' => $this->selectedDate,
                'time' => $this->selectedTime,
                'status' => $doctor->specialty->id == 1 ? 'Booked' : 'Requested',
            ]);

            foreach($this->servicesData as $service)
            {
                AppointmentService::create([
                    'appointment_id' => $appointment->id,
                    'service_id' => $service['id'],
                    'covered' => $service['included'],
                ]);
            }

            $notificationResult = $appointmentNotificationService->send($appointment);
        }

        $successMessage = 'Cita almacenada exitosamente!';

        if ($notificationResult !== null) {
            $successMessage = match (true) {
                $notificationResult['ok'] => 'Cita almacenada y notificacion enviada por WhatsApp.',
                $notificationResult['attempted'] => 'Cita almacenada, pero no fue posible enviar la notificacion de WhatsApp.',
                default => 'Cita almacenada. La notificacion de WhatsApp no esta configurada.',
            };
        }

        // Show success toast
        $this->dispatch('notify',
            type: 'success',
            content: $successMessage,
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

        $this->offices = $this->doctor->offices;
        $this->selectedOffice = $this->appointment->office->id;
        $this->selectedDate = $this->appointment->date->format('Y-m-d');
        $this->selectedTime = $this->appointment->time->format('H:i');

        $this->selectedServices = $appointmentServices->pluck('id')->toArray();
        $this->servicesData = $appointmentServices->map(fn ($appointmentService) => [
            'id' => $appointmentService->service_id,
            'name' => $appointmentService->service->name,
            'included' => $appointmentService->covered,
        ])->values()->toArray();
        $this->services = Service::whereIn('id', $this->selectedServices)
            ->get()
            ->keyBy('id');
    }

    public function getServicesProperty()
    {
        if (!$this->selectedDoctor) {
            return collect();
        }

        return Doctor::with('specialty.services')
            ->find($this->selectedDoctor)?->specialty?->services ?? collect();
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

        $usedSlots = Appointment::whereDate('date', $this->selectedDate)->where('status', 'Booked')
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

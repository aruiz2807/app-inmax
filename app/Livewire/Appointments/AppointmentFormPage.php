<?php

namespace App\Livewire\Appointments;

use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Doctor;
use App\Models\Office;
use App\Models\Parameter;
use App\Models\Policy;
use App\Models\Service;
use App\Models\User;
use App\Models\PolicyService;
use App\Services\Appointments\AppointmentRequestNotificationService;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
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
    public $unregisteredServices = [];
    public $newUnregisteredService = '';

    public $user;
    public $doctor;

    public $doctors;
    public $policies;
    public $offices;

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
            $this->selectedTime = $this->availableHours[0]['id'] ?? null;
        }
    }

    public function updatedSelectedUser($value)
    {
        $this->user = User::find($value);
    }

    public function updatedSelectedDoctor($value)
    {
        $this->reset([
            'selectedServices',
        ]);

        $this->doctor = Doctor::find($value);
        $this->offices = $this->doctor?->offices ?? collect();
        $this->selectedOffice = null;
    }

    public function clear()
    {
        $this->reset([
            'selectedUser',
            'selectedDoctor',
            'selectedOffice',
            'selectedServices',
            'unregisteredServices',
            'newUnregisteredService',
            'selectedDate',
            'selectedTime',
            'offices',
        ]);

        $this->selectedDate = $this->availableDates[0]['id'];
        $this->selectedTime = $this->availableHours[0]['id'] ?? null;
    }

    public function addUnregisteredService()
    {
        $this->validate([
            'newUnregisteredService' => 'required|string|max:255',
        ]);

        $this->unregisteredServices[] = $this->newUnregisteredService;
        $this->newUnregisteredService = '';

        $this->dispatch('close-custom-service-modal');
    }

    public function removeUnregisteredService($index)
    {
        unset($this->unregisteredServices[$index]);
        $this->unregisteredServices = array_values($this->unregisteredServices);
    }

    public function schedule(AppointmentRequestNotificationService $appointmentNotificationService)
    {
        $this->validate([
            'selectedServices' => 'required_without:unregisteredServices|array',
            'unregisteredServices' => 'required_without:selectedServices|array',
            'selectedDoctor' => 'required|exists:doctors,id',
        ], [
            'selectedServices.required_without' => 'Debe seleccionar al menos un servicio.',
            'unregisteredServices.required_without' => 'Debe seleccionar al menos un servicio.',
            'selectedDoctor.required' => 'Debe seleccionar un doctor.',
        ]);

        if (empty($this->selectedServices) && empty($this->unregisteredServices)) {
            $this->addError('selectedServices', 'Debe seleccionar al menos un servicio.');
            return;
        }

        if(!$this->selectedDoctor) {
            $this->addError('selectedDoctor', 'Debe seleccionar un doctor.');
            return;
        }

        $doctorHasOffices = Doctor::find($this->selectedDoctor)?->offices()->exists();
        if(!$this->selectedOffice && $doctorHasOffices) {
            $this->addError('selectedOffice', 'Debe seleccionar un consultorio.');
            return;
        }

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
            // Fetch Medico General specialty 
            $paramSpecialty = Parameter::where('type', 'MG')->where('key', 'Especialidad')->first();

            $appointment = Appointment::create([
                'user_id' => $this->selectedUser,
                'doctor_id' => $this->selectedDoctor,
                'office_id' => $this->selectedOffice,
                'requested_by_user_id' => Auth::user()->id,
                'date' => $this->selectedDate,
                'time' => $this->selectedTime,
                'status' => $doctor->specialty->id == $paramSpecialty->value ? \App\Enums\AppointmentStatus::BOOKED : \App\Enums\AppointmentStatus::REQUESTED,
            ]);

            foreach($this->servicesData as $service)
            {
                AppointmentService::create([
                    'appointment_id' => $appointment->id,
                    'service_id' => $service['id'] ?? null,
                    'unregistered_service' => $service['unregistered_service'] ?? null,
                    'covered' => $service['included'],
                ]);
            }

            $notificationResult = $appointmentNotificationService->send($appointment);
        }

        $successMessage = '¡Cita almacenada exitosamente!';

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

        if($this->appointment->doctor_id)
        {
            $this->selectedDoctor = (string) $this->appointment->doctor_id;
            $this->doctor = Doctor::find($this->selectedDoctor);
            $this->offices = $this->doctor->offices;
        }
        else
        {
            $this->offices = Office::all();
        }

        $this->selectedOffice = (string) $this->appointment->office_id;
        $this->selectedDate = $this->appointment->date->format('Y-m-d');
        $this->selectedTime = $this->appointment->time->format('H:i');

        $this->selectedServices = $appointmentServices->whereNotNull('service_id')->pluck('service_id')->toArray();
        $this->unregisteredServices = $appointmentServices->whereNull('service_id')->pluck('unregistered_service')->toArray();
    }

    #[Computed]
    public function services()
    {
        if (!$this->selectedDoctor) {
            return collect();
        }

        return Doctor::with('doctorServices.service')
            ->find($this->selectedDoctor)
            ?->doctorServices
            ->map(fn($ds) => $ds->service)
            ->filter()
            ->values() ?? collect();
    }

    #[Computed]
    public function servicesData()
    {
        if (!$this->selectedUser) {
            return [];
        }

        if (empty($this->selectedServices) && empty($this->unregisteredServices)) {
            return [];
        }

        $user = User::with('policy')->find($this->selectedUser);
        if (!$user?->policy) return [];

        $policyId = $user->policy->type === 'Member'
            ? $user->policy->parent_policy_id
            : $user->policy->id;

        $data = collect();

        if (!empty($this->selectedServices)) {
            $services = Service::whereIn('id', $this->selectedServices)->get();

            $data = $services->map(function ($service) use ($policyId) {
                $isCovered = PolicyService::where('policy_id', $policyId)
                    ->where('service_id', $service->id)
                    ->whereColumn('used', '<', 'included')
                    ->exists();

                return [
                    'id' => $service->id,
                    'unregistered_service' => null,
                    'name' => $service->name,
                    'included' => $isCovered,
                ];
            });
        }

        foreach ($this->unregisteredServices as $index => $unregistered) {
            $data->push([
                'id' => null,
                'unregistered_service' => $unregistered,
                'name' => $unregistered,
                'included' => false, // Custom services are not covered by default
                'index' => $index,
            ]);
        }

        return $data->toArray();
    }

    public function getAvailableDatesProperty()
    {
        $dates = [];
        $date = Carbon::now();

        // CASO EXCEPCIONAL
        $inmaxOffices = [1,2];
        $discardedDays = [];
        if (in_array($this->selectedOffice, $inmaxOffices)) {
            $discardedDays = ['2026-07-09', '2026-07-10', '2026-07-11'];
        }

        while (count($dates) < 15) {
            if (!$date->isSunday() && !in_array($date->format('Y-m-d'), $discardedDays)) {
                $dates[] = [
                    'id'    => $date->format('Y-m-d'),
                    'day'   => $date->isoFormat('ddd'),
                    'num'   => $date->format('d'),
                    'month' => $date->isoFormat('MMM'),
                ];
            }

            $date->addDay();
        }

        return $dates;
    }

    public function getAvailableHoursProperty()
    {
        if (!$this->selectedDate) {
            return [];
        }

        $doctor = Doctor::find($this->selectedDoctor);
        $usedSlots = [];
        $endHour = 22; // 10 PM

        // Fetch Medico General specialty
        $paramSpecialty = Parameter::where('type', 'MG')->where('key', 'Especialidad')->first();
        $isMedicoGeneral = $doctor?->specialty_id == $paramSpecialty?->value;
        $isSaturday = Carbon::parse($this->selectedDate)->isSaturday();

        if (Carbon::parse($this->selectedDate)->isToday()) {
            $startHour = now()->addHours(2)->hour;
        }
        else {
            $startHour = 7;
        }

        $slots = [];

        if ($startHour >= 7 && $startHour <= 22) {
            for ($hour = $startHour; $hour <= $endHour; $hour++) {
                $slots[] = Carbon::createFromTime($hour)->format('h:00 A');
            }
        }

        if($isMedicoGeneral && $this->selectedOffice)
        {
            $usedSlots = Appointment::whereDate('date', $this->selectedDate)
                ->where('office_id', $this->selectedOffice)
                ->where('status', \App\Enums\AppointmentStatus::BOOKED)
                ->pluck('time')
                ->map(fn ($time) => Carbon::parse($time)->format('H:i'))
                ->toArray();

            if($isSaturday) {
                $slots = array_filter($slots, function ($slot) use ($startHour) {
                    $hour = Carbon::createFromFormat('h:i A', $slot)->hour;
                    return $hour >= $startHour && $hour <= 13; // Only show slots from startHour to 1 PM on Saturdays
                });
            } else {
                $slots = Office::find($this->selectedOffice)->officeHours
                ->filter(function ($item) use ($startHour) {
                    $hour = Carbon::createFromFormat('h:i A', $item->slot)->hour;
                    return $hour >= $startHour;
                })
                ->sortBy(fn ($item) => Carbon::createFromFormat('h:i A', $item->slot))
                ->pluck('slot')
                ->toArray();
            }
        }

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

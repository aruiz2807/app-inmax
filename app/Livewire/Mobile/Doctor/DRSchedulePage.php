<?php

namespace App\Livewire\Mobile\Doctor;

use App\Enums\DoctorType;
use App\Livewire\Mobile\Doctor\DRScheduleConfirmationPage;
use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Doctor;
use App\Models\Office;
use App\Models\Parameter;
use App\Models\PolicyService;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Computed;
use Livewire\Component;

class DRSchedulePage extends Component
{
    public $appointment;

    public $selectedDate;
    public $selectedTime;

    public $selectedDoctor;
    public $selectedOffice;
    public $selectedServices = [];
    public $serviceSearch = '';
    public $servicesLimit = 20;

    public $user;
    public $doctor;

    public $doctors;
    public $offices;

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.doctor.schedule-page');
    }

    public function mount($appointment)
    {
        $this->appointment = Appointment::findOrFail($appointment);
        $doctorsQuery = Doctor::where('status', 'Active');
        $paramSpecialty = Parameter::where('type', 'MG')->where('key', 'Especialidad')->first();

        $currentDoctor = Auth::user()?->doctor;
        $currentDoctorType = $currentDoctor?->type;
        if (in_array($currentDoctorType, [DoctorType::Provider], true)) {
            $doctorsQuery->where(function ($query) use ($paramSpecialty, $currentDoctor) {
                $query->where(function ($doctorQuery) use ($paramSpecialty) {
                    $doctorQuery->where('type', DoctorType::Doctor)
                        ->where('specialty_id', $paramSpecialty->value);
                });

                if ($currentDoctor?->id) {
                    $query->orWhere('id', $currentDoctor->id);
                }
            });
        }

        $this->doctors = $doctorsQuery->get();
        $this->user = $this->appointment->user;

        $this->selectedDate = $this->availableDates[0]['id'];
        $this->selectedTime = $this->availableHours[0]['id'] ?? null;
    }

    public function updatedSelectedDoctor($value)
    {
        $this->reset([
            'selectedServices',
            'serviceSearch',
            'servicesLimit',
        ]);

        $this->doctor = Doctor::find($value);
        $this->offices = $this->doctor?->offices ?? collect();
    }

    public function updatedServiceSearch()
    {
        $this->servicesLimit = 20;
    }

    public function loadMoreServices()
    {
        $this->servicesLimit += 20;
    }

    public function schedule()
    {
        $this->validate([
            'selectedServices' => 'required|array|min:1',
        ], [
            'selectedServices.required' => 'Debe seleccionar al menos un servicio.',
            'selectedServices.min' => 'Debe seleccionar al menos un servicio.',
        ]);

        $doctor = Doctor::find($this->selectedDoctor);
        // Fetch Medico General specialty 
        $paramSpecialty = Parameter::where('type', 'MG')->where('key', 'Especialidad')->first();

        $appointment = Appointment::create([
            'user_id' => $this->appointment->user->id,
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
                'service_id' => $service['id'],
                'covered' => $service['included'],
            ]);
        }

        session()->flash('appointment_confirmation_id', $appointment->id);

        return $this->redirect(DRScheduleConfirmationPage::class);
    }

    #[Computed]
    public function services()
    {
        if (!$this->selectedDoctor) {
            return collect();
        }

        $allServices = Doctor::with('doctorServices.service')
            ->find($this->selectedDoctor)
            ?->doctorServices
            ->map(fn($ds) => $ds->service)
            ->filter()
            ->values() ?? collect();

        $filtered = $allServices;
        if ($this->serviceSearch) {
            $normalizedSearch = Str::lower(Str::ascii($this->serviceSearch));
            
            $filtered = $allServices->filter(function($s) use ($normalizedSearch) {
                return Str::contains(
                    Str::lower(Str::ascii($s->name)), 
                    $normalizedSearch
                );
            });
        }

        // Always include currently selected services
        $selected = $allServices->whereIn('id', $this->selectedServices);

        return $filtered->take($this->servicesLimit)->merge($selected)->unique('id')->values();
    }

    #[Computed]
    public function servicesData()
    {
        if (!$this->user || !$this->selectedServices || empty($this->selectedServices)) {
            return [];
        }

        $policyId = $this->user->policy->type === 'Member'
            ? $this->user->policy->parent_policy_id
            : $this->user->policy->id;

        $services = Service::whereIn('id', $this->selectedServices)->get();

        return $services->map(function ($service) use ($policyId) {
            $isCovered = PolicyService::where('policy_id', $policyId)
                ->where('service_id', $service->id)
                ->whereColumn('used', '<', 'included')
                ->exists();

            return [
                'id' => $service->id,
                'name' => $service->name,
                'included' => $isCovered,
            ];
        })->toArray();
    }

    public function getAvailableDatesProperty()
    {
        $dates = [];
        $date = Carbon::now();

        while (count($dates) < 15) {
            if (!$date->isSunday()) {
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

        if($doctor?->specialty_id == 1 && $this->selectedOffice)
        {
            $usedSlots = Appointment::whereDate('date', $this->selectedDate)
                ->where('office_id', $this->selectedOffice)
                ->where('status', \App\Enums\AppointmentStatus::BOOKED)
                ->pluck('time')
                ->map(fn ($time) => Carbon::parse($time)->format('H:i'))
                ->toArray();

            $slots = Office::find($this->selectedOffice)->officeHours
                ->sortBy(fn ($item) => Carbon::createFromFormat('h:i A', $item->slot))
                ->pluck('slot')
                ->toArray();
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

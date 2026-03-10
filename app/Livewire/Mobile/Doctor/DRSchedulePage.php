<?php

namespace App\Livewire\Mobile\Doctor;

use App\Livewire\Mobile\Doctor\DRScheduleConfirmationPage;
use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Doctor;
use App\Models\PolicyService;
use App\Models\Service;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

class DRSchedulePage extends Component
{
    public $appointment;
    public $selectedDate;
    public $selectedTime;
    public $selectedServices = [];
    public $selectedDoctor;
    public $availableDates = [];
    public $availableHours = [];
    public $isIncluded = 1;
    public $services;
    public $doctors;
    public $user;
    public $doctor;
    public $servicesData = [];

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.doctor.schedule-page');
    }

    public function mount($appointment)
    {
        $this->appointment = Appointment::findOrFail($appointment);
        $this->services = Service::where('status', 'Active')->get();
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
    }

    public function updatedSelectedDoctor($value)
    {
        $this->selectedDoctor = $value;
        $this->doctor = Doctor::find($value);
        $this->services = $this->doctor->specialty->services;
    }

    public function updatedSelectedServices($value)
    {
        $this->user = $this->appointment->user;

        $policyId = $this->user->policy->type === 'Member'
            ? $this->user->policy->parent_policy_id
            : $this->user->policy->id;

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

    public function updatedSelectedDate($value)
    {
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

    public function schedule()
    {
        $appointment = Appointment::create([
            'user_id' => $this->appointment->user->id,
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

        session()->flash('appointment_confirmation_id', $appointment->id);

        return $this->redirect(DRScheduleConfirmationPage::class);
    }
}

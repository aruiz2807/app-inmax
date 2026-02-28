<?php

namespace App\Livewire\Mobile\Doctor;

use App\Livewire\Mobile\Doctor\DRScheduleConfirmationPage;
use App\Models\Appointment;
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
    public $selectedService;
    public $selectedDoctor;
    public $availableDates = [];
    public $availableHours = [];
    public $isIncluded;
    public $services;
    public $doctors;
    public $user;

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.doctor.schedule-page');
    }

    public function mount($appointment)
    {
        $this->appointment = Appointment::findOrFail($appointment);
        $this->services = Service::where('status', 'Active')->get();

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

    public function updatedSelectedService($value)
    {
        $this->doctors = Doctor::where('specialty_id', $value)->get();
    }

    public function updatedSelectedDoctor($value)
    {
        $user = $this->appointment->user;

        if($user->policy->type === 'Member')
        {
            $policy_id  = $user->policy->parent_policy_id;
        }
        else
        {
            $policy_id  = $user->policy->id;
        }

        $this->isIncluded = PolicyService::query()
            ->where('policy_id', $policy_id)
            ->where('service_id', $this->selectedService)
            ->whereColumn('used', '<', 'included')
            ->exists();
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

        session()->flash('appointment_confirmation_id', $appointment->id);

        return $this->redirect(DRScheduleConfirmationPage::class);
    }
}

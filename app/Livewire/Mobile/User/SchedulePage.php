<?php

namespace App\Livewire\Mobile\User;

use App\Livewire\Mobile\User\ScheduleConfirmationPage;
use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Doctor;
use App\Models\Office;
use App\Models\PolicyService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

class SchedulePage extends Component
{
    public $selectedOffice;
    public $selectedDate;
    public $selectedTime;
    public $availableOffices = [];
    public $isIncluded;
    public $user;
    public $offices;

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.user.schedule-page');
    }

    public function mount()
    {
        $this->user = Auth::user();
        $this->offices = Office::all();

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
            ->where('service_id', 1) //consulta medico general, revisar como no pasar hardcodeado
            ->whereColumn('used', '<', 'included')
            ->exists();

        $this->selectedOffice = 1;
        $this->selectedDate = now()->addDay()->format('Y-m-d');
        $this->selectedTime = '09:00';
    }

    public function getAvailableOfficesProperty()
    {
        return Office::all()->map(function ($office) {
            return [
                'id'      => $office->id,
                'name'    => $office->name,
                'address' => $office->address,
            ];
        })->toArray();
    }

    public function getAvailableDatesProperty()
    {
        $dates = [];

        if($this->user->policy->start_date->isBefore(today()))
        {
            $date = Carbon::now();
        }
        else
        {
            $date = $this->user->policy->start_date;
        }

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

    public function schedule()
    {
        $doctor = Doctor::where('office_id', $this->selectedOffice)->inRandomOrder()->first(); //get random doctor

        $appointment = Appointment::create([
            'user_id' => Auth::user()->id,
            'doctor_id' => $doctor->id,
            'date' => $this->selectedDate,
            'time' => $this->selectedTime,
            'covered' => $this->isIncluded,
            'status' => 'Booked',
        ]);

        AppointmentService::create([
            'appointment_id' => $appointment->id,
            'service_id' => 1, //revisar como no pasar hardcodeado
            'covered' => $this->isIncluded,
        ]);

        session()->flash('appointment_confirmation_id', $appointment->id);

        return $this->redirect(ScheduleConfirmationPage::class);
    }
}

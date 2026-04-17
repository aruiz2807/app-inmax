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
        $this->selectedDate = $this->availableDates[0]['id'];
        $this->selectedTime = $this->availableHours[0]['id'];
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
            ->where('office_id', $this->selectedOffice)
            ->where('status', 'Booked')
            ->pluck('time')
            ->map(fn ($time) => Carbon::parse($time)->format('H:i'))
            ->toArray();

        $slots = Office::find($this->selectedOffice)->officeHours
            ->sortBy(fn ($item) => Carbon::createFromFormat('h:i A', $item->slot))
            ->pluck('slot')
            ->toArray();

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
        $appointment = Appointment::create([
            'user_id' => Auth::user()->id,
            'office_id' => $this->selectedOffice,
            'date' => $this->selectedDate,
            'time' => $this->selectedTime,
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

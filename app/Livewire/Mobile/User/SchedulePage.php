<?php

namespace App\Livewire\Mobile\User;

use App\Livewire\Mobile\User\ScheduleConfirmationPage;
use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Doctor;
use App\Models\Office;
use App\Models\Parameter;
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
    public ?int $mgServiceId = null;

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

        // Fetch Consulta Medico General param
        $param = Parameter::where('type', 'MG')->where('key', 'Consulta')->first();

        if ($param && !empty($param->value)) 
        {
            $this->mgServiceId = (int) $param->value;
        }

        $this->isIncluded = false;

        if ($this->mgServiceId) {
            $this->isIncluded = PolicyService::query()
                ->where('policy_id', $policy_id)
                ->where('service_id', $this->mgServiceId)  
                ->whereColumn('used', '<', 'included')
                ->exists();
        }

        $this->selectedOffice = 1;
        $this->selectedDate = $this->availableDates[0]['id'];
        $this->selectedTime = $this->availableHours[0]['id'] ?? null;
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

        // CASO EXCEPCIONAL
        $inmaxOffices = [1,2];
        $discardedDays = [];
        if (in_array($this->selectedOffice, $inmaxOffices)) {
            $discardedDays = ['2026-07-09', '2026-07-10', '2026-07-11'];
        }
        
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

            if (!$date->isSunday() && !in_array($date->format('Y-m-d'), $discardedDays)) {
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
        $isSaturday = Carbon::parse($this->selectedDate)->isSaturday();

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

        if ($isSaturday) {
            $slots = array_filter($slots, function ($slot) {
                $hour = Carbon::createFromFormat('h:i A', $slot)->hour;
                return $hour >= 9 && $hour <= 13; // Only show slots from 9 AM to 1 PM on Saturdays
            });
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

    public function schedule()
    {
        $appointment = Appointment::create([
            'user_id' => Auth::user()->id,
            'office_id' => $this->selectedOffice,
            'date' => $this->selectedDate,
            'time' => $this->selectedTime,
            'status' => \App\Enums\AppointmentStatus::BOOKED,
        ]);

        if ($this->mgServiceId) {
            AppointmentService::create([
                'appointment_id' => $appointment->id,
                'service_id' => $this->mgServiceId,
                'covered' => $this->isIncluded,
            ]);
        }

        session()->flash('appointment_confirmation_id', $appointment->id);

        return $this->redirect(ScheduleConfirmationPage::class);
    }
}

<?php

namespace App\Livewire\Mobile\User;

use App\Enums\DoctorType;
use App\Models\Appointment;
use App\Models\AppointmentService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

class RecordPage extends Component
{
    public $appointments;
    public $exams;
    public $doctorAppointments;

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.user.record-page');
    }

    public function mount()
    {
        $user = Auth::user();

        $this->appointments = Appointment::where([
            ['user_id', $user->id],
            ['status', \App\Enums\AppointmentStatus::COMPLETED],
        ])->get();

        $this->doctorAppointments  = Appointment::where([
            ['user_id', $user->id],
            ['status', \App\Enums\AppointmentStatus::COMPLETED],
        ])
        ->whereHas('doctor', function ($query) {
            $query->where('type', DoctorType::Doctor);
        })
        ->get();

        $this->exams = AppointmentService::query()
            ->with('appointment')
            ->where('status', \App\Enums\AppointmentStatus::COMPLETED)
            ->whereHas('appointment', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('status', \App\Enums\AppointmentStatus::COMPLETED);
            })
            ->get();
    }
}

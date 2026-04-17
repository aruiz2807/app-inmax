<?php

namespace App\Livewire\Mobile\Doctor;

use App\Enums\DoctorType;
use App\Enums\ExternalServicesType;
use App\Models\PolicyExternalService;
use App\Models\Appointment;
use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\Layout;

class DRRecordPage extends Component
{
    public $appointments;
    public $exams;
    public $user;
    public $doctorAppointments;
    public $externalServices;
    
    public bool $showUploadForm = false;

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.user.record-page');
    }

    public function mount($user)
    {
        $this->user = User::findOrFail($user);

        $this->appointments = Appointment::where([
            ['user_id', $this->user->id],
            ['status', \App\Enums\AppointmentStatus::COMPLETED],
        ])->get();

        $this->doctorAppointments  = Appointment::where([
            ['user_id', $this->user->id],
            ['status', \App\Enums\AppointmentStatus::COMPLETED],
        ])
        ->whereHas('doctor', function ($query) {
            $query->where('type', DoctorType::Doctor);
        })
        ->get();

        $this->exams = Appointment::where([
            ['user_id', $this->user->id],
            ['status', \App\Enums\AppointmentStatus::COMPLETED],
        ])
        ->whereHas('note', function ($query) {
            $query->whereNotNull('attachment_path');
        })
        ->get();

        $policy_id = $this->user->policy->id;
        if ($policy_id) {
            $this->externalServices = PolicyExternalService::where('policy_id', $policy_id)
                ->orderBy('date', 'desc')
                ->get();
        } else {
            $this->externalServices = collect();
        }
    }
}

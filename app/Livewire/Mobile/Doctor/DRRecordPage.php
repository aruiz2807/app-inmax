<?php

namespace App\Livewire\Mobile\Doctor;

use App\Enums\DoctorType;
use App\Enums\ExternalServicesType;
use App\Models\PolicyExternalService;
use App\Models\Appointment;
use App\Models\User;
use App\Models\Parameter;
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
    public bool $isMobileDevice = true;

    public function render()
    {
        $view = $this->isMobileDevice
            ? 'livewire.mobile.user.record-page'
            : 'livewire.doctor.record-page';

        $layout = $this->isMobileDevice ? 'layouts.mobile' : 'layouts.app';

        return view($view)->layout($layout);
    }

    public function mount($user)
    {
        $this->isMobileDevice = $this->detectMobileDevice();
        $desktopVersionEnabled = Parameter::where('type', 'SITE')->where('key', 'Doctor_VersionDesktop')->first()->value == 'Activa';
        !$desktopVersionEnabled ? $this->isMobileDevice = true : '';

        $this->loadRecord($user);
    }

    public function loadRecord($user)
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

    protected function detectMobileDevice()
    {
        $forcedDevice = request()->query('device');

        if ($forcedDevice === 'mobile') {
            return true;
        }

        if ($forcedDevice === 'desktop') {
            return false;
        }

        $userAgent = strtolower((string) request()->userAgent());

        return preg_match('/android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini|mobile/i', $userAgent) === 1;
    }
}

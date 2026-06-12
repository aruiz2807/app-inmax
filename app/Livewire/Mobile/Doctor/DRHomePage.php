<?php

namespace App\Livewire\Mobile\Doctor;

use App\Models\Appointment;
use App\Models\Parameter;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DRHomePage extends Component
{
    public $todayAppointments = null;
    public $user;
    public $pendingRequestsCount = 0;
    public $showRequestsAlert = true;
    public $paramGMSpeciality;
    public bool $isMobileDevice = true;

    public function render()
    {
        $view = $this->isMobileDevice
            ? 'livewire.mobile.doctor.home-page'
            : 'livewire.doctor.home-page';

        $layout = $this->isMobileDevice ? 'layouts.mobile' : 'layouts.app';
        
        return view($view)->layout($layout);
    }

    public function mount()
    {
        $this->isMobileDevice = $this->detectMobileDevice();
        $desktopVersionEnabled = Parameter::where('type', 'SITE')->where('key', 'Doctor_VersionDesktop')->first()->value == 'Activa';
        $desktopVersionEnabled ? $this->isMobileDevice = false : $this->isMobileDevice = true;

        $this->loadTodayAppointments();
        $this->checkPendingRequests();
        $this->paramGMSpeciality = Parameter::where('type', 'MG')->where('key', 'Especialidad')->first();
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

    public function loadTodayAppointments()
    {
        $this->user = Auth::user();
        $this->todayAppointments = Appointment::where([
                ['status', \App\Enums\AppointmentStatus::BOOKED],
                ['doctor_id', Auth::user()->doctor->id],
            ])
            ->whereDate('date', today())
            ->orderBy('time')
            ->get();
    }

    public function checkPendingRequests()
    {
        $this->pendingRequestsCount = Appointment::where([
                ['status', \App\Enums\AppointmentStatus::REQUESTED],
                ['doctor_id', Auth::user()->doctor->id],
            ])->count();
    }

    public function dismissRequestsAlert()
    {
        $this->showRequestsAlert = false;
    }
}

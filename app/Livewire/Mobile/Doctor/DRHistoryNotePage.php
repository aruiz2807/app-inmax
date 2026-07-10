<?php

namespace App\Livewire\Mobile\Doctor;

use App\Enums\DoctorType;
use App\Models\Appointment;
use App\Models\AppointmentService;
use App\Models\Parameter;
use Livewire\Component;


class DRHistoryNotePage extends Component
{
    public $appointment;
    public $services;
    public $isDoctor;
    public bool $isMobileDevice = true;

    public function render()
    {
        $view = $this->isMobileDevice
            ? 'livewire.mobile.doctor.history-note-page'
            : 'livewire.doctor.history-note-page';

        $layout = $this->isMobileDevice ? 'layouts.mobile' : 'layouts.app';

        return view($view)->layout($layout);
    }

    public function mount($appointment)
    {
        $this->isMobileDevice = $this->detectMobileDevice();
        $desktopVersionEnabled = Parameter::where('type', 'SITE')->where('key', 'Doctor_VersionDesktop')->first()->value == 'Activa';
        !$desktopVersionEnabled ? $this->isMobileDevice = true : '';

        $this->appointment = Appointment::with(['note', 'prescriptions.medication', 'doctor', 'user.policy'])->findOrFail($appointment);
        $this->services = AppointmentService::where([
            ['appointment_id', $this->appointment->id],
            ['status', \App\Enums\AppointmentStatus::COMPLETED],
        ])->get();
        $this->isDoctor = $this->appointment->doctor->type === DoctorType::Doctor;
    }

    protected function detectMobileDevice(): bool
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

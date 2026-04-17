<?php

namespace App\Livewire\Mobile\Doctor;

use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

class DRHomePage extends Component
{
    public $todayAppointments = null;
    public $user;
    public $pendingRequestsCount = 0;
    public $showRequestsAlert = true;

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.doctor.home-page');
    }

    public function mount()
    {
        $this->loadTodayAppointments();
        $this->checkPendingRequests();
    }

    public function loadTodayAppointments()
    {
        $this->user = Auth::user();
        $this->todayAppointments = Appointment::where([
                ['status', 'Booked'],
                ['doctor_id', Auth::user()->doctor->id],
            ])
            ->whereDate('date', today())
            ->orderBy('time')
            ->get();
    }

    public function checkPendingRequests()
    {
        $this->pendingRequestsCount = Appointment::where([
                ['status', 'Requested'],
                ['doctor_id', Auth::user()->doctor->id],
            ])->count();
    }

    public function dismissRequestsAlert()
    {
        $this->showRequestsAlert = false;
    }
}

<?php

namespace App\Livewire\Mobile\Doctor;

use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

class DRHomePage extends Component
{
    public $todayAppointments = null;

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.doctor.home-page');
    }

    public function mount()
    {
        $this->loadTodayAppointments();
    }

    public function loadTodayAppointments()
    {
        $this->todayAppointments = Appointment::where([
                ['status', 'Booked'],
                ['doctor_id', Auth::user()->id],
            ])
            ->whereDate('date', '=', today())
            ->orderBy('time')
            ->get();
    }
}

<?php

namespace App\Livewire\Mobile\User;

use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ScheduleConfirmationPage extends Component
{
    public $appointment;

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.user.schedule-confirmation-page');
    }

    public function mount()
    {
        $appointmentId = session('appointment_confirmation_id');

        abort_unless($appointmentId, 404);

        $this->appointment = Appointment::where('id', $appointmentId)
            ->where('user_id', Auth::user()->id)
            ->firstOrFail();

        session()->forget('appointment_confirmation_id');
    }
}

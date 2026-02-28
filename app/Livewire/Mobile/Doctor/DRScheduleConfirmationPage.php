<?php

namespace App\Livewire\Mobile\Doctor;

use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class DRScheduleConfirmationPage extends Component
{
    public $appointment;

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.doctor.schedule-confirmation-page');
    }

    public function mount()
    {
        $appointmentId = session('appointment_confirmation_id');

        abort_unless($appointmentId, 404);

        $this->appointment = Appointment::where('id', $appointmentId)->firstOrFail();

        session()->forget('appointment_confirmation_id');
    }
}

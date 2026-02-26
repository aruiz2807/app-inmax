<?php

namespace App\Livewire\Mobile\Doctor;

use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class NoShowConfirmationPage extends Component
{
    public $appointment;

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.doctor.no-show-confirmation-page');
    }

    public function mount()
    {
        $appointmentId = session('appointment_noshow_id');

        abort_unless($appointmentId, 404);

        $this->appointment = Appointment::where('id', $appointmentId)->firstOrFail();

    }
}

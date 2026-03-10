<?php

namespace App\Livewire\Mobile\User;

use App\Models\Appointment;
use Livewire\Attributes\Layout;
use Livewire\Component;

class RatingConfirmationPage extends Component
{
    public $appointment;

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.user.rating-confirmation-page');
    }

    public function mount()
    {
        $appointmentId = session('appointment_rating_id');

        abort_unless($appointmentId, 404);

        $this->appointment = Appointment::where('id', $appointmentId)->firstOrFail();
    }
}

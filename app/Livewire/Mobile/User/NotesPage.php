<?php

namespace App\Livewire\Mobile\User;

use App\Models\Appointment;
use Livewire\Attributes\Layout;
use Livewire\Component;

class NotesPage extends Component
{
    public $appointment;

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.user.notes-page');
    }

    public function mount($appointment)
    {
        $this->appointment = Appointment::findOrFail($appointment);
    }
}

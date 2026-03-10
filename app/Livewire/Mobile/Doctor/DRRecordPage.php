<?php

namespace App\Livewire\Mobile\Doctor;

use App\Models\Appointment;
use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\Layout;

class DRRecordPage extends Component
{
    public $appointments;
    public $exams;
    public $user;

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.user.record-page');
    }

    public function mount($user)
    {
        $this->user = User::findOrFail($user);

        $this->appointments = Appointment::where([
            ['user_id', $this->user->id],
            ['status', 'Completed'],
        ])->get();

        $this->exams = Appointment::where([
            ['user_id', $this->user->id],
            ['status', 'Completed'],
        ])
        ->whereHas('note', function ($query) {
            $query->whereNotNull('attachment_path');
        })
        ->get();
    }
}

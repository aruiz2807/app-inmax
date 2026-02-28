<?php

namespace App\Livewire\Mobile\User;

use App\Models\Appointment;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

class RecordPage extends Component
{
    public $appointments;
    public $exams;


    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.user.record-page');
    }

    public function mount()
    {
        $user = Auth::user();

        $this->appointments = Appointment::where([
            ['user_id', $user->id],
            ['status', 'Completed'],
        ])->get();

        $this->exams = Appointment::where([
            ['user_id', $user->id],
            ['status', 'Completed'],
        ])
        ->whereHas('note', function ($query) {
            $query->whereNotNull('attachment_path');
        })
        ->get();
    }
}

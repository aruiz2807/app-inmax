<?php

namespace App\Livewire\Mobile\User;

use Livewire\Component;
use Livewire\Attributes\Layout;

class RecordPage extends Component
{
    public $appointments = null;
    public $diagnoses = null;
    public $exams = null;
    public $medications = null;

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.user.record-page');
    }
}

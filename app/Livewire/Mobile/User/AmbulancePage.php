<?php

namespace App\Livewire\Mobile\User;

use App\Models\Parameter;
use Livewire\Component;
use Livewire\Attributes\Layout;

class AmbulancePage extends Component
{
    public $phone;

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.user.ambulance-page');
    }

    public function mount()
    {
        $this->phone = "tel:" . Parameter::where('type', 'AMB')->where('key', 'Phone')->value('value') ?? '3313666626';
    }
}
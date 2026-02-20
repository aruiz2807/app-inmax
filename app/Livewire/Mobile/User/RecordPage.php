<?php

namespace App\Livewire\Mobile\User;

use Livewire\Component;
use Livewire\Attributes\Layout;

class RecordPage extends Component
{
    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.user.record-page');
    }
}

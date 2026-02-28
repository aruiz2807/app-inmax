<?php

namespace App\Livewire\Mobile;

use Livewire\Attributes\Layout;
use Livewire\Component;

class ContactPage extends Component
{
    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.contact-page');
    }
}

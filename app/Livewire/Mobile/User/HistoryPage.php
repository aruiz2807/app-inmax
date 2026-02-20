<?php

namespace App\Livewire\Mobile\User;

use Livewire\Component;
use Livewire\Attributes\Layout;

class HistoryPage extends Component
{
    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.user.history-page');
    }
}

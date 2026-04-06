<?php

namespace App\Livewire\Home;

use Livewire\Attributes\Layout;
use Livewire\Component;

class DashboardPage extends Component
{
    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.home.dashboard-page');
    }
}

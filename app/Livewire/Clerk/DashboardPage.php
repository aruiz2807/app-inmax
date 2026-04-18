<?php

namespace App\Livewire\Clerk;

use Livewire\Attributes\Layout;
use Livewire\Component;

class DashboardPage extends Component
{
    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.clerk.dashboard-page');
    }
}

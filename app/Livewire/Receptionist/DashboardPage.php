<?php

namespace App\Livewire\Receptionist;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

class DashboardPage extends Component
{
    #[Url(as: 'tab')]
    public string $tab = 'all';

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['all', 'pending', 'paid'], true)) {
            return;
        }

        $this->tab = $tab;
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.receptionist.dashboard-page');
    }
}

<?php

namespace App\Livewire\Dispatcher;

use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

class TransportsPage extends Component
{
    #[Url(as: 'tab')]
    public string $tab = 'booked';

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['booked', 'completed', 'cancelled', 'all'], true)) {
            return;
        }

        $this->tab = $tab;
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.dispatcher.transports-page');
    }
}
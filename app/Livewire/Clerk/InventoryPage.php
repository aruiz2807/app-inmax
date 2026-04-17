<?php

namespace App\Livewire\Clerk;

use Livewire\Attributes\Layout;
use Livewire\Component;

class InventoryPage extends Component
{
    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.clerk.inventory-page');
    }
}

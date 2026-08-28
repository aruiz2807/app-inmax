<?php

namespace App\Livewire\Mobile\User;

use App\Livewire\Concerns\WithPatientHistoryModal;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;

class RecordPage extends Component
{
    use WithFileUploads;
    use WithPatientHistoryModal;

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.user.record-history-page');
    }

    public function mount()
    {
        $this->loadPatientHistory(Auth::id());
    }
}

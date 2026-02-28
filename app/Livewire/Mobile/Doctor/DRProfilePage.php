<?php

namespace App\Livewire\Mobile\Doctor;

use App\Livewire\Mobile\ContactPage;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

class DRProfilePage extends Component
{
    public $user = null;

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.doctor.profile-page');
    }

    public function mount()
    {
        $this->user = Auth::user();
    }

    public function record($id)
    {
        dd($id);
    }

    public function help()
    {
        return $this->redirect(ContactPage::class);
    }
}

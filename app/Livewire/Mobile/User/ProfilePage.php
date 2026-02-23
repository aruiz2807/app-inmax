<?php

namespace App\Livewire\Mobile\User;

use App\Livewire\Mobile\User\ContactPage;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

class ProfilePage extends Component
{
    public $user = null;

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.user.profile-page');
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

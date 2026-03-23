<?php

namespace App\Livewire\Mobile;

use Livewire\Attributes\Layout;
use Livewire\Component;

use App\Models\Parameter;

class ContactPage extends Component
{
    public $socialLinks = [];

    #[Layout('layouts.mobile')]
    public function render()
    {
        return view('livewire.mobile.contact-page', [
            'socialLinks' => $this->socialLinks,
        ]);
    }

    public function mount()
    {
        $this->socialLinks = Parameter::where('type', 'RS')
            ->orderByRaw("FIELD(`key`, 'Phone', 'Email', 'Maps', 'WhatsApp', 'Instagram', 'Tiktok')")
        ->get();
    }
}

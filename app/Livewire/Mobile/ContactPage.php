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
            'socialLinksIcons' => $this->socialLinksIcons,
            'socialLinksList' => $this->socialLinksList,
        ]);
    }

    public function mount()
    {
        $listOfKeys = ['Page', 'Facebook', 'Instagram', 'Tiktok', 'WhatsApp', 'Maps'];

        $this->socialLinksIcons = Parameter::where('type', 'RS')->get();

        $this->socialLinksList = Parameter::where('type', 'RS')->whereIn('key', $listOfKeys)
            ->orderByRaw("FIELD(`key`, 'Page', 'Facebook', 'Instagram', 'Tiktok', 'WhatsApp', 'Maps')")
        ->get();
    }
}

<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\Auth\PinSetupTokenService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ForgotPinPage extends Component
{
    #[Validate('required|digits:10')]
    public string $phone = '';

    public ?string $generatedPinSetupUrl = null;

    #[Layout('layouts.guest')]
    public function render()
    {
        return view('livewire.auth.forgot-pin-page');
    }

    public function sendResetLink(PinSetupTokenService $tokenService): void
    {
        $this->validate();

        $user = User::query()->where('phone', $this->phone)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'phone' => __('No existe un usuario con ese telefono.'),
            ]);
        }

        $result = $tokenService->generateSetupLink($user, null, PinSetupTokenService::PURPOSE_RESET);

        $this->generatedPinSetupUrl = $result['url'];

        $content = match (true) {
            ($result['whatsapp']['ok'] ?? false) => 'Enlace de restablecimiento enviado por WhatsApp.',
            ($result['whatsapp']['attempted'] ?? false) => 'No se pudo enviar WhatsApp. Se muestra enlace para pruebas.',
            default => 'WhatsApp no esta configurado. Se muestra enlace para pruebas.',
        };

        $this->dispatch(
            'notify',
            type: 'success',
            content: $content,
            duration: 4000
        );
    }
}

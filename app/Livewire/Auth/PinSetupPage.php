<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\Auth\PinSetupTokenService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

class PinSetupPage extends Component
{
    public string $token = '';
    public ?User $user = null;

    #[Validate('required|digits:6|confirmed')]
    public string $pin = '';

    public string $pin_confirmation = '';

    #[Layout('layouts.guest')]
    public function render()
    {
        return view('livewire.auth.pin-setup-page');
    }

    public function mount(string $token, PinSetupTokenService $tokenService): void
    {
        $this->token = $token;

        $setupToken = $tokenService->resolveActiveToken($this->token);

        if (! $setupToken) {
            abort(404);
        }

        $this->user = $setupToken->user;
    }

    public function save(PinSetupTokenService $tokenService)
    {
        $this->validate();

        $setupToken = $tokenService->resolveActiveToken($this->token);

        if (! $setupToken) {
            throw ValidationException::withMessages([
                'pin' => __('El enlace para configurar PIN es invalido o ya expiro.'),
            ]);
        }

        $tokenService->consumeToken($setupToken, $this->pin);

        return redirect()
            ->route('login')
            ->with('status', __('PIN configurado correctamente. Inicia sesion con tu telefono y PIN.'));
    }
}

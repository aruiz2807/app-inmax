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
    public string $tokenStatus = PinSetupTokenService::STATUS_INVALID;
    public ?string $tokenMessage = null;

    #[Validate('required|digits:4|confirmed')]
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
        $resolved = $tokenService->resolveTokenStatus($this->token);

        $this->tokenStatus = $resolved['status'];
        $this->user = $resolved['token']?->user;
        $this->tokenMessage = $this->resolveTokenMessage($this->tokenStatus);
    }

    public function save(PinSetupTokenService $tokenService)
    {
        $this->validate();

        $resolved = $tokenService->resolveTokenStatus($this->token);
        $this->tokenStatus = $resolved['status'];
        $this->tokenMessage = $this->resolveTokenMessage($this->tokenStatus);

        if ($this->tokenStatus !== PinSetupTokenService::STATUS_ACTIVE || ! $resolved['token']) {
            throw ValidationException::withMessages([
                'pin' => __($this->tokenMessage),
            ]);
        }

        $tokenService->consumeToken($resolved['token'], $this->pin);

        return redirect()
            ->route('login')
            ->with('status', __('PIN configurado correctamente. Inicia sesion con tu telefono y PIN.'));
    }

    public function canSetPin(): bool
    {
        return $this->tokenStatus === PinSetupTokenService::STATUS_ACTIVE;
    }

    private function resolveTokenMessage(string $status): string
    {
        return match ($status) {
            PinSetupTokenService::STATUS_USED => 'Este enlace ya fue usado. Solicita uno nuevo al administrador.',
            PinSetupTokenService::STATUS_EXPIRED => 'Este enlace ya vencio. Solicita uno nuevo al administrador.',
            PinSetupTokenService::STATUS_ACTIVE => '',
            default => 'Este enlace es invalido. Solicita uno nuevo al administrador.',
        };
    }
}

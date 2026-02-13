<?php

namespace App\Livewire\Users;

use App\Livewire\Forms\UsersForm;
use App\Models\User;
use App\Services\Auth\PinSetupTokenService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class UsersPage extends Component
{
    public UsersForm $form;
    public ?int $userId = null;
    public ?string $lastPinSetupUrl = null;
    public ?string $lastPinSetupName = null;
    public ?string $lastPinSetupPhone = null;

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.users.users-page');
    }

    #[On('editUser')]
    public function edit(int $userId): void
    {
        $user = User::findOrFail($userId);

        $this->form->set($user);
        $this->userId = $userId;

        $this->dispatch('open-user-modal');
    }

    #[On('sendUserPinSetupLink')]
    public function sendPinSetupLink(int $userId, PinSetupTokenService $tokenService): void
    {
        $user = User::findOrFail($userId);
        $result = $tokenService->generateSetupLink($user, Auth::user());

        $this->lastPinSetupUrl = $result['url'];
        $this->lastPinSetupName = $user->name;
        $this->lastPinSetupPhone = $user->phone;

        $this->dispatch(
            'notify',
            type: 'success',
            content: 'Enlace de configuracion de PIN generado y simulado por WhatsApp.',
            duration: 4000
        );
    }

    public function save(PinSetupTokenService $tokenService): void
    {
        if ($this->userId) {
            $this->form->update($this->userId);

            $this->dispatch(
                'notify',
                type: 'success',
                content: 'Usuario actualizado exitosamente.',
                duration: 4000
            );
        } else {
            $user = $this->form->store();
            $result = $tokenService->generateSetupLink($user, Auth::user());

            $this->lastPinSetupUrl = $result['url'];
            $this->lastPinSetupName = $user->name;
            $this->lastPinSetupPhone = $user->phone;

            $this->dispatch(
                'notify',
                type: 'success',
                content: 'Usuario creado y enlace de PIN enviado.',
                duration: 4000
            );
        }

        $this->dispatch('close-user-modal');
        $this->dispatch('pg:eventRefresh-usersTable');
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->form->reset();
        $this->userId = null;
    }
}

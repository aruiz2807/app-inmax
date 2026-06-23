<?php

namespace App\Livewire\Users;

use App\Livewire\Forms\UsersForm;
use App\Models\Doctor;
use App\Models\Permission;
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
    public ?int $permissionsUserId = null;
    public ?string $permissionsUserName = null;
    public array $assignedPermissionIds = [];

    #[Layout('layouts.app')]
    public function render()
    {
        $doctors = Doctor::with('user:id,name')
            ->orderBy('id')
            ->get();

        $permissionsByGroup = Permission::query()
            ->where('is_active', true)
            ->orderBy('group_name')
            ->orderBy('name')
            ->get(['id', 'name', 'code', 'group_name', 'description'])
            ->groupBy(fn (Permission $permission): string => $permission->group_name ?: 'Sin grupo');

        return view('livewire.users.users-page', [
            'doctors' => $doctors,
            'permissionsByGroup' => $permissionsByGroup,
        ]);
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
        $purpose = $user->pin_set_at
            ? PinSetupTokenService::PURPOSE_RESET
            : PinSetupTokenService::PURPOSE_SYSTEM_USER_ACTIVATION;

        $result = $tokenService->generateSetupLink($user, Auth::user(), $purpose);

        $this->lastPinSetupUrl = $result['url'];
        $this->lastPinSetupName = $user->name;
        $this->lastPinSetupPhone = $user->phone;

        $content = match (true) {
            ($result['whatsapp']['ok'] ?? false) => 'Enlace de PIN enviado por WhatsApp correctamente.',
            ($result['whatsapp']['attempted'] ?? false) => 'No se pudo enviar WhatsApp. Se genero enlace de prueba.',
            default => 'No hay configuracion de WhatsApp. Se genero enlace de prueba.',
        };

        $this->dispatch(
            'notify',
            type: 'success',
            content: $content,
            duration: 4000
        );
    }

    public function save(PinSetupTokenService $tokenService): void
    {
        $needsDoctors = in_array($this->form->profile, ['Clerk', 'Receptionist']);

        if ($needsDoctors && empty($this->form->doctorIds)) {
            $this->addError('form.doctorIds', 'Debe asignar al menos un doctor.');
            return;
        }

        if ($this->userId) {
            $this->form->update($this->userId);

            $user = User::findOrFail($this->userId);

            if ($needsDoctors) {
                $user->staffDoctors()->sync($this->form->doctorIds);
            } else {
                $user->staffDoctors()->detach();
            }

            $this->dispatch(
                'notify',
                type: 'success',
                content: 'Usuario actualizado exitosamente.',
                duration: 4000
            );
        } else {
            $user = $this->form->store();

            if ($needsDoctors) {
                $user->staffDoctors()->sync($this->form->doctorIds);
            }

            $result = $tokenService->generateSetupLink(
                $user,
                Auth::user(),
                PinSetupTokenService::PURPOSE_SYSTEM_USER_ACTIVATION
            );

            $this->lastPinSetupUrl = $result['url'];
            $this->lastPinSetupName = $user->name;
            $this->lastPinSetupPhone = $user->phone;

            $content = match (true) {
                ($result['whatsapp']['ok'] ?? false) => 'Usuario creado y enlace de activacion enviado por WhatsApp.',
                ($result['whatsapp']['attempted'] ?? false) => 'Usuario creado. No se pudo enviar WhatsApp, enlace disponible para prueba.',
                default => 'Usuario creado. Falta configurar WhatsApp, enlace disponible para prueba.',
            };

            $this->dispatch(
                'notify',
                type: 'success',
                content: $content,
                duration: 4000
            );
        }

        $this->dispatch('close-user-modal');
        $this->dispatch('pg:eventRefresh-usersTable');
        $this->resetForm();
    }

    #[On('manageUserPermissions')]
    public function manageUserPermissions(int $userId): void
    {
        $user = User::query()
            ->with('permissions:id')
            ->findOrFail($userId);

        $this->permissionsUserId = $user->id;
        $this->permissionsUserName = $user->name;
        $this->assignedPermissionIds = $user->permissions
            ->pluck('id')
            ->map(fn (int $id): string => (string) $id)
            ->all();

        $this->dispatch('open-user-permissions-modal');
    }

    public function saveUserPermissions(): void
    {
        if (! $this->permissionsUserId) {
            return;
        }

        $user = User::query()->findOrFail($this->permissionsUserId);

        $permissionIds = Permission::query()
            ->whereIn('id', $this->assignedPermissionIds)
            ->pluck('id')
            ->map(fn (int $id): int => $id)
            ->all();

        $user->permissions()->sync($permissionIds);

        $this->dispatch(
            'notify',
            type: 'success',
            content: 'Permisos del usuario actualizados exitosamente.',
            duration: 4000
        );

        $this->dispatch('close-user-permissions-modal');
        $this->dispatch('pg:eventRefresh-usersTable');
        $this->resetPermissionAssignment();
    }

    public function resetForm(): void
    {
        $this->form->reset();
        $this->userId = null;
    }

    public function resetPermissionAssignment(): void
    {
        $this->permissionsUserId = null;
        $this->permissionsUserName = null;
        $this->assignedPermissionIds = [];
    }
}

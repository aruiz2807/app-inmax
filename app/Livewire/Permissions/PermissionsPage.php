<?php

namespace App\Livewire\Permissions;

use App\Livewire\Forms\PermissionForm;
use App\Models\Permission;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

class PermissionsPage extends Component
{
    public PermissionForm $form;
    public ?int $permissionId = null;

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.permissions.permissions-page');
    }

    #[On('editPermission')]
    public function edit(int $permissionId): void
    {
        $permission = Permission::query()->findOrFail($permissionId);

        $this->form->set($permission);
        $this->permissionId = $permission->id;

        $this->dispatch('open-permission-modal');
    }

    public function save(): void
    {
        if ($this->permissionId) {
            $this->form->update($this->permissionId);

            $message = 'Permiso actualizado exitosamente.';
        } else {
            $this->form->store();

            $message = 'Permiso creado exitosamente.';
        }

        $this->dispatch(
            'notify',
            type: 'success',
            content: $message,
            duration: 4000
        );

        $this->dispatch('close-permission-modal');
        $this->dispatch('pg:eventRefresh-permissionsTable');
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->form->reset();
        $this->form->is_active = true;
        $this->permissionId = null;
    }
}

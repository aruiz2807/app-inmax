<?php

namespace App\Livewire\Forms;

use App\Models\Permission;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Form;

class PermissionForm extends Form
{
    #[Validate(['required', 'string', 'max:255'])]
    public string $name = '';

    #[Validate(['required', 'string', 'max:255', 'regex:/^[a-z0-9._-]+$/'])]
    public string $code = '';

    #[Validate(['nullable', 'string', 'max:255'])]
    public string $group_name = '';

    #[Validate(['nullable', 'string', 'max:1000'])]
    public string $description = '';

    #[Validate(['boolean'])]
    public bool $is_active = true;

    /**
     * Store the permission in DB.
     */
    public function store(): Permission
    {
        $this->validate();

        return Permission::query()->create([
            'name' => trim($this->name),
            'code' => trim($this->code),
            'group_name' => trim($this->group_name) !== '' ? trim($this->group_name) : null,
            'description' => trim($this->description) !== '' ? trim($this->description) : null,
            'is_active' => $this->is_active,
        ]);
    }

    /**
     * Set the permission form state.
     */
    public function set(Permission $permission): void
    {
        $this->name = $permission->name;
        $this->code = $permission->code;
        $this->group_name = $permission->group_name ?? '';
        $this->description = $permission->description ?? '';
        $this->is_active = $permission->is_active;
    }

    /**
     * Update the permission in DB.
     */
    public function update(int $permissionId): void
    {
        Validator::make([
            'name' => $this->name,
            'code' => $this->code,
            'group_name' => $this->group_name,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ], [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z0-9._-]+$/',
                Rule::unique('permissions', 'code')->ignore($permissionId),
            ],
            'group_name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['boolean'],
        ])->validate();

        $permission = Permission::query()->findOrFail($permissionId);

        $permission->update([
            'name' => trim($this->name),
            'group_name' => trim($this->group_name) !== '' ? trim($this->group_name) : null,
            'description' => trim($this->description) !== '' ? trim($this->description) : null,
            'is_active' => $this->is_active,
        ]);
    }
}

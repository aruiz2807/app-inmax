<div>
    <x-slot name="header">
        {{ __('Users') }}
    </x-slot>

    <div>
        <x-ui.card size="full">
            <x-ui.heading class="flex items-center justify-between mb-4" level="h3" size="sm">
                <span>Catalogo de usuarios</span>

                <x-ui.modal.trigger id="users-modal" wire:click="resetForm">
                    <x-ui.button color="teal" icon="plus-circle">
                        Agregar usuario
                    </x-ui.button>
                </x-ui.modal.trigger>
            </x-ui.heading>
            <p>Administre usuarios y el envio de enlaces para configuracion de PIN</p>
        </x-ui.card>
    </div>

    @if ($lastPinSetupUrl)
        <div class="pt-2">
            <x-ui.card size="full">
                <x-ui.heading level="h3" size="sm">
                    Ultimo enlace generado
                </x-ui.heading>

                <p class="text-sm mt-2">
                    Usuario: <span class="font-semibold">{{ $lastPinSetupName }}</span>
                    ({{ $lastPinSetupPhone }})
                </p>

                <a href="{{ $lastPinSetupUrl }}" class="ui-link block mt-2 break-all" target="_blank" rel="noopener noreferrer">
                    {{ $lastPinSetupUrl }}
                </a>
            </x-ui.card>
        </div>
    @endif

    <div class="pt-2">
        <x-ui.card size="full">
            <livewire:users.users-table />
        </x-ui.card>
    </div>

    <x-ui.modal
        id="users-modal"
        animation="fade"
        width="2xl"
        heading="{{ $userId ? 'Editar usuario' : 'Nuevo usuario' }}"
        description="Ingrese la siguiente informacion para registrar un usuario"
        x-on:close-user-modal.window="$data.close()"
        x-on:open-user-modal.window="$data.open()"
    >
        <form wire:submit="save">
            <x-ui.fieldset label="Informacion del usuario">
                <x-ui.field required>
                    <x-ui.label>Nombre completo</x-ui.label>
                    <x-ui.input wire:model="form.name" name="name" placeholder="Nombre Apellido" />
                    <x-ui.error name="form.name" />
                </x-ui.field>

                <x-ui.field required>
                    <x-ui.label>Correo electrónico</x-ui.label>
                    <x-ui.input wire:model="form.email" name="email" type="email" placeholder="nombre@correo.com" />
                    <x-ui.error name="form.email" />
                </x-ui.field>

                <x-ui.field required>
                    <x-ui.label>Celular</x-ui.label>
                    <x-ui.input wire:model="form.phone" name="phone" placeholder="3300000000" />
                    <x-ui.error name="form.phone" />
                </x-ui.field>

                <x-ui.field required>
                    <x-ui.label>Perfil</x-ui.label>
                    <x-ui.select wire:model.live="form.profile" placeholder="Selecciona un perfil">
                        <x-ui.select.option value="Admin">Administrador</x-ui.select.option>
                        <x-ui.select.option value="Sales">Vendedor</x-ui.select.option>
                        <x-ui.select.option value="Clerk">{{ __('app.clerk') }}</x-ui.select.option>
                        <x-ui.select.option value="Receptionist">Recepcionista</x-ui.select.option>
                    </x-ui.select>
                    <x-ui.error name="form.profile" />
                </x-ui.field>

                @if (in_array($form->profile, ['Clerk', 'Receptionist']))
                    <x-ui.field required>
                        <x-ui.label>Proveedores asignados</x-ui.label>
                        <p class="text-xs text-neutral-500 mb-2">Selecciona al menos un doctor, laboratorio u hospital al que pertenece este usuario.</p>

                        @if ($doctors->isEmpty())
                            <p class="text-sm text-neutral-500 italic">No hay doctores registrados en el sistema.</p>
                        @else
                            <div class="max-h-48 overflow-y-auto border border-neutral-200 rounded-lg divide-y divide-neutral-100">
                                @foreach ($doctors as $doctor)
                                    <label class="flex items-center gap-3 px-4 py-2.5 hover:bg-neutral-50 cursor-pointer">
                                        <input
                                            type="checkbox"
                                            wire:model="form.doctorIds"
                                            value="{{ $doctor->id }}"
                                            class="rounded border-neutral-300 text-teal-600 focus:ring-teal-500"
                                        />
                                        <span class="text-sm font-medium">{{ $doctor->user?->name ?? 'Sin nombre' }}</span>
                                    </label>
                                @endforeach
                            </div>
                        @endif

                        <x-ui.error name="form.doctorIds" />
                    </x-ui.field>
                @endif
            </x-ui.fieldset>

            <div class="w-full flex justify-end gap-3 pt-4">
                <x-ui.button x-on:click="$data.close();" icon="x-mark" variant="outline">
                    Cancel
                </x-ui.button>

                <x-ui.button type="submit" icon="check" variant="primary" color="teal">
                    Guardar
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    <x-ui.modal
        id="user-permissions-modal"
        animation="fade"
        width="3xl"
        heading="Permisos del usuario"
        description="{{ $permissionsUserName ? 'Asigne los permisos directos para '.$permissionsUserName.'.' : 'Asigne permisos directos al usuario.' }}"
        x-on:close-user-permissions-modal.window="$data.close()"
        x-on:open-user-permissions-modal.window="$data.open()"
    >
        <form wire:submit="saveUserPermissions">
            <x-ui.fieldset label="Permisos asignados">
                @forelse ($permissionsByGroup as $groupName => $permissions)
                    <div class="space-y-3">
                        <div>
                            <h4 class="text-sm font-semibold text-neutral-800">{{ $groupName }}</h4>
                            <p class="text-xs text-neutral-500">Selecciona los permisos que el usuario podra utilizar dentro del sistema.</p>
                        </div>

                        <x-ui.checkbox.group wire:model="assignedPermissionIds">
                            <div class="grid gap-3 md:grid-cols-2">
                                @foreach ($permissions as $permission)
                                    <x-ui.checkbox
                                        value="{{ (string) $permission->id }}"
                                        :label="$permission->name"
                                        :description="$permission->description ?: $permission->code"
                                        variant="card"
                                    />
                                @endforeach
                            </div>
                        </x-ui.checkbox.group>
                    </div>
                @empty
                    <p class="text-sm text-neutral-500 italic">No hay permisos activos disponibles para asignar.</p>
                @endforelse
            </x-ui.fieldset>

            <div class="w-full flex justify-end gap-3 pt-4">
                <x-ui.button x-on:click="$data.close();" icon="x-mark" variant="outline">
                    Cancelar
                </x-ui.button>

                <x-ui.button type="submit" icon="check" variant="primary" color="teal">
                    Guardar permisos
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>

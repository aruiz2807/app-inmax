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
                    <x-ui.input wire:model="form.name" name="name" placeholder="Juan Perez" />
                    <x-ui.error name="form.name" />
                </x-ui.field>

                <x-ui.field required>
                    <x-ui.label>Correo electronico</x-ui.label>
                    <x-ui.input wire:model="form.email" name="email" type="email" placeholder="juan.perez@mail.com" />
                    <x-ui.error name="form.email" />
                </x-ui.field>

                <x-ui.field required>
                    <x-ui.label>Celular</x-ui.label>
                    <x-ui.input wire:model="form.phone" name="phone" placeholder="3310203040" />
                    <x-ui.error name="form.phone" />
                </x-ui.field>

                <x-ui.field required>
                    <x-ui.label>Codigo pais</x-ui.label>
                    <x-ui.input wire:model="form.phone_country_code" name="phone_country_code" placeholder="52" />
                    <x-ui.error name="form.phone_country_code" />
                </x-ui.field>

                <x-ui.field required>
                    <x-ui.label>Perfil</x-ui.label>
                    <x-ui.select wire:model="form.profile" placeholder="Selecciona un perfil">
                        <x-ui.select.option value="Admin">Admin</x-ui.select.option>
                        <x-ui.select.option value="Doctor">Doctor</x-ui.select.option>
                        <x-ui.select.option value="Sales">Sales</x-ui.select.option>
                        <x-ui.select.option value="User">User</x-ui.select.option>
                    </x-ui.select>
                    <x-ui.error name="form.profile" />
                </x-ui.field>
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
</div>

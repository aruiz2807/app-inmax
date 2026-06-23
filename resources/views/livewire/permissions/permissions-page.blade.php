<div>
    <x-slot name="header">
        {{ __('app.permissions') }}
    </x-slot>

    <div>
        <x-ui.card size="full">
            <x-ui.heading class="flex items-center justify-between mb-4" level="h3" size="sm">
                <span>Catalogo de permisos</span>

                <x-ui.modal.trigger id="permission-modal" wire:click="resetForm">
                    <x-ui.button color="teal" icon="plus-circle">
                        Agregar permiso
                    </x-ui.button>
                </x-ui.modal.trigger>
            </x-ui.heading>
            <p>Administre los permisos disponibles para menu, rutas y asignacion a usuarios.</p>
        </x-ui.card>
    </div>

    <div class="pt-2">
        <x-ui.card size="full">
            <livewire:permissions.permissions-table />
        </x-ui.card>
    </div>

    <x-ui.modal
        id="permission-modal"
        animation="fade"
        width="2xl"
        heading="{{ $permissionId ? 'Editar permiso' : 'Nuevo permiso' }}"
        description="Registre o actualice el catalogo de permisos."
        x-on:close-permission-modal.window="$data.close()"
        x-on:open-permission-modal.window="$data.open()"
    >
        <form wire:submit="save">
            <x-ui.fieldset label="Informacion del permiso">
                <x-ui.field required>
                    <x-ui.label>Nombre</x-ui.label>
                    <x-ui.input wire:model="form.name" placeholder="Ver membresias" />
                    <x-ui.error name="form.name" />
                </x-ui.field>

                <x-ui.field required>
                    <x-ui.label>Codigo tecnico</x-ui.label>
                    <x-ui.input
                        wire:model="form.code"
                        placeholder="view.admin.policies"
                        @disabled($permissionId)
                    />
                    <p class="text-xs text-neutral-500 mt-1">
                        @if ($permissionId)
                            El codigo tecnico no se puede editar despues de crear el permiso.
                        @else
                            Usa minusculas y separadores como punto, guion o guion bajo.
                        @endif
                    </p>
                    <x-ui.error name="form.code" />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Grupo</x-ui.label>
                    <x-ui.input wire:model="form.group_name" placeholder="Administracion" />
                    <x-ui.error name="form.group_name" />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Descripcion</x-ui.label>
                    <x-ui.textarea wire:model="form.description" rows="3" placeholder="Permite acceder al catalogo principal de membresias." />
                    <x-ui.error name="form.description" />
                </x-ui.field>

                <x-ui.field>
                    <x-checkbox id="permission_is_active" wire:model="form.is_active">
                        Permiso activo
                    </x-checkbox>
                    <x-ui.error name="form.is_active" />
                </x-ui.field>
            </x-ui.fieldset>

            <div class="w-full flex justify-end gap-3 pt-4">
                <x-ui.button x-on:click="$data.close();" icon="x-mark" variant="outline">
                    Cancelar
                </x-ui.button>

                <x-ui.button type="submit" icon="check" variant="primary" color="teal">
                    Guardar
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>

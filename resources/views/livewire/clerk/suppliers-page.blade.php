<div>
    <x-slot name="header">
        {{ __('app.suppliers') }}
    </x-slot>

    <div>
        <x-ui.card size="full">
            <x-ui.heading class="flex items-center justify-between mb-4" level="h3" size="sm">
                <span>Catalogo de proveedores</span>

                <x-ui.modal.trigger id="supplier-modal" wire:click="resetForm">
                    <x-ui.button color="teal" icon="plus-circle">
                        Agregar proveedor
                    </x-ui.button>
                </x-ui.modal.trigger>
            </x-ui.heading>
            <p>Administre el catalogo de proveedores del modulo de compras e inventario</p>
        </x-ui.card>
    </div>

    <div class="pt-2">
        <x-ui.card size="full">
            <livewire:clerk.suppliers-table />
        </x-ui.card>
    </div>

    <x-ui.modal
        id="supplier-modal"
        animation="fade"
        width="2xl"
        heading="{{ $supplierId ? 'Editar proveedor' : 'Nuevo proveedor' }}"
        description="Ingrese la siguiente informacion para registrar un proveedor"
        x-on:close-supplier-modal.window="$data.close()"
        x-on:open-supplier-modal.window="$data.open()"
    >
        <form wire:submit="save">
            @csrf

            <x-ui.fieldset label="Informacion del proveedor">
                <x-ui.field required>
                    <x-ui.label>Nombre</x-ui.label>
                    <x-ui.input wire:model="form.name" name="name" placeholder="Nombre del proveedor" />
                    <x-ui.error name="form.name" />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>RFC</x-ui.label>
                    <x-ui.input wire:model="form.rfc" name="rfc" placeholder="XAXX010101000" maxlength="13" />
                    <x-ui.error name="form.rfc" />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Correo electronico</x-ui.label>
                    <x-ui.input wire:model="form.email" name="email" type="email" placeholder="proveedor@correo.com" />
                    <x-ui.error name="form.email" />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Telefono</x-ui.label>
                    <x-ui.input wire:model="form.phone" name="phone" placeholder="3300000000" />
                    <x-ui.error name="form.phone" />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Direccion</x-ui.label>
                    <x-ui.textarea wire:model="form.address" name="address" rows="3" placeholder="Direccion fiscal o comercial" />
                    <x-ui.error name="form.address" />
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

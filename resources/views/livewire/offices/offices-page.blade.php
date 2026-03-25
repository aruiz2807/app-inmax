<div>
    <x-slot name="header">
        {{ __('app.offices') }}
    </x-slot>

    <div>
        <x-ui.card size="full">
            <x-ui.heading class="flex items-center justify-between mb-4" level="h3" size="sm">
                <span>Catalogo de consultorios</span>

                <x-ui.modal.trigger id="office-modal" wire:click="resetForm">
                    <x-ui.button color="teal" icon="plus-circle">
                        Agregar consultorio
                    </x-ui.button>
                </x-ui.modal.trigger>
            </x-ui.heading>

            <p>Administre los consultorios</p>
        </x-ui.card>
    </div>

    <div class="pt-2">
        <x-ui.card size="full">
            <livewire:offices.offices-table />
        </x-ui.card>
    </div>

    <x-ui.modal
        id="office-modal"
        animation="fade"
        width="2xl"
        heading="{{$officeId ? 'Editar consultorio' : 'Nuevo consultorio'}}"
        description="Ingrese la siguiente información para registrar un consultorio"
        x-on:close-office-modal.window="$data.close()"
        x-on:open-office-modal.window="$data.open()"
    >
        <form wire:submit="save">
            <x-ui.fieldset label="Información del consultorio">
                 <x-ui.field required>
                    <x-ui.label>Nombre del consultorio</x-ui.label>
                    <x-ui.input wire:model="form.name" name="name" placeholder="Consultorio Angel Nuño" />
                    <x-ui.error name="form.name" />
                </x-ui.field>
            
                <x-ui.field required>
                    <x-ui.label>Dirección</x-ui.label>
                    <x-ui.textarea wire:model="form.address" name="address" />
                    <x-ui.error name="form.address" />
                </x-ui.field>

                <x-ui.field required>
                    <x-ui.label>Google Maps URL</x-ui.label>
                    <x-ui.textarea wire:model="form.maps_url" name="maps_url" />
                    <x-ui.error name="form.maps_url" />
                </x-ui.field>

            </x-ui.fieldset>

            <div class="w-full flex justify-end gap-3 pt-4">
                <x-ui.button x-on:click="$data.close();" wire:click="resetForm" icon="x-mark" variant="outline">
                    Cancel
                </x-ui.button>

                <x-ui.button type="submit" icon="check" variant="primary" color="teal">
                    Guardar
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>

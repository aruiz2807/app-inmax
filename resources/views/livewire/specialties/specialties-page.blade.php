<div>
    <x-slot name="header">
        {{ __('app.specialties') }}
    </x-slot>

    <div>
        <x-ui.card size="full">
            <x-ui.heading class="flex items-center justify-between mb-4" level="h3" size="sm">
                <span>Catalogo de especialidades</span>

                <x-ui.modal.trigger id="specialty-modal" wire:click="resetForm">
                    <x-ui.button color="teal" icon="plus-circle">
                        Agregar especialidad
                    </x-ui.button>
                </x-ui.modal.trigger>
            </x-ui.heading>
            <p>Administre las especilidades que pueden ser asignadas a los medicos</p>
        </x-ui.card>
    </div>

    <div class="pt-2">
        <x-ui.card size="full">
            <livewire:specialties.specialties-table />
        </x-ui.card>
    </div>

    <x-ui.modal
        id="specialty-modal"
        animation="fade"
        width="2xl"
        heading="{{$specialtyId ? 'Editar especialidad' : 'Nueva especialidad'}}"
        description="Ingrese la siguiente información para registrar una especialidad"
        x-on:close-specialty-modal.window="$data.close()"
        x-on:open-specialty-modal.window="$data.open()"
    >
        <form wire:submit="save">
            @csrf

            <x-ui.fieldset label="Información de la especialidad">
                <x-ui.field required>
                    <x-ui.label>Nombre</x-ui.label>
                    <x-ui.input wire:model="form.name" name="name" placeholder="Pediatría" />
                    <x-ui.error name="form.name" />
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

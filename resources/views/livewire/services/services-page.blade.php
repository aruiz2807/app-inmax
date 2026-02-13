<div>
    <x-slot name="header">
        {{ __('Services') }}
    </x-slot>

    <div>
        <x-ui.card size="full">
            <x-ui.heading class="flex items-center justify-between mb-4" level="h3" size="sm">
                <span>Catalogo de servicios</span>

                <x-ui.modal.trigger id="service-modal" wire:click="resetForm">
                    <x-ui.button color="teal" icon="plus-circle">
                        Agregar servicio
                    </x-ui.button>
                </x-ui.modal.trigger>
            </x-ui.heading>
            <p>Administre los servicios que pueden ser incluidos en las coberturas</p>
        </x-ui.card>
    </div>

    <div class="pt-2">
        <x-ui.card size="full">
            <livewire:services.services-table />
        </x-ui.card>
    </div>

    <x-ui.modal
        id="service-modal"
        animation="fade"
        width="2xl"
        heading="{{$serviceId ? 'Editar Servicio' : 'Nuevo servicio'}}"
        description="Ingrese la siguiente información para registrar un servicio"
        x-on:close-service-modal.window="$data.close()"
        x-on:open-service-modal.window="$data.open()"
    >
        <form wire:submit="save">
            @csrf

            <x-ui.fieldset label="Información del servicio">
                <x-ui.field required>
                    <x-ui.label>Nombre</x-ui.label>
                    <x-ui.input wire:model="form.name" name="name" placeholder="Consulta de medico general" />
                    <x-ui.error name="form.name" />
                </x-ui.field>

                <x-ui.radio.group wire:model="form.type" name="type" label="Tipo de servicio" variant="cards" direction="horizontal" class="mt-4">
                    <x-ui.radio.item
                        icon="ticket"
                        value="Event"
                        label="Evento"
                        description="Ocaciones en las que puede ser utilizado el servicio mientras la poliza este vigente"
                        checked
                    />
                    <x-ui.radio.item
                        icon="banknotes"
                        value="Amount"
                        label="Importe"
                        description="Importe asignado de un solo uso. (Ej. $1,200 para medicamentos)"
                    />
                </x-ui.radio.group>
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

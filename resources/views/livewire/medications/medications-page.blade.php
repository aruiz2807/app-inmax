<div>
    <x-slot name="header">
        {{ __('app.medications') }}
    </x-slot>

    <div>
        <x-ui.card size="full">
            <x-ui.heading class="flex items-center justify-between mb-4" level="h3" size="sm">
                <span>Catálogo de medicamentos</span>

                <x-ui.modal.trigger id="medication-modal" wire:click="resetForm">
                    <x-ui.button color="teal" icon="plus-circle">
                        Agregar medicamento
                    </x-ui.button>
                </x-ui.modal.trigger>
            </x-ui.heading>
            <p>Administre los medicamentos disponibles en el sistema</p>
        </x-ui.card>
    </div>

    <div class="pt-2">
        <x-ui.card size="full">
            <livewire:medications.medications-table />
        </x-ui.card>
    </div>

    <x-ui.modal
        id="medication-modal"
        animation="fade"
        width="4xl"
        heading="{{$medicationId ? 'Editar Medicamento' : 'Nuevo medicamento'}}"
        description="Ingrese la siguiente información para registrar un medicamento"
        x-on:close-medication-modal.window="$data.close()"
        x-on:open-medication-modal.window="$data.open()"
    >
        <form wire:submit="save">
            @csrf

            <x-ui.fieldset label="Información del medicamento">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-ui.field required>
                        <x-ui.label>Código</x-ui.label>
                        <x-ui.input wire:model="form.code" name="code" placeholder="MED001" maxlength="6" />
                        <x-ui.error name="form.code" />
                    </x-ui.field>

                    <x-ui.field required>
                        <x-ui.label>Nombre</x-ui.label>
                        <x-ui.input wire:model="form.name" name="name" placeholder="Paracetamol" />
                        <x-ui.error name="form.name" />
                    </x-ui.field>

                    <x-ui.field required>
                        <x-ui.label>Nombre Comercial</x-ui.label>
                        <x-ui.input wire:model="form.trade_name" name="trade_name" placeholder="Tempra" />
                        <x-ui.error name="form.trade_name" />
                    </x-ui.field>

                    <x-ui.field required>
                        <x-ui.label>Sustancia Activa</x-ui.label>
                        <x-ui.input wire:model="form.active_substance" name="active_substance" placeholder="Paracetamol 500mg" />
                        <x-ui.error name="form.active_substance" />
                    </x-ui.field>

                    <x-ui.field required>
                        <x-ui.label>Laboratorio</x-ui.label>
                        <x-ui.input wire:model="form.lab" name="lab" placeholder="Laboratorios ABC" />
                        <x-ui.error name="form.lab" />
                    </x-ui.field>

                    <x-ui.field required>
                        <x-ui.label>Presentación</x-ui.label>
                        <x-ui.input wire:model="form.packaging" name="packaging" placeholder="Caja con 20 tabletas" />
                        <x-ui.error name="form.packaging" />
                    </x-ui.field>

                    <x-ui.field required>
                        <x-ui.label>Precio Público</x-ui.label>
                        <x-ui.input wire:model="form.price_public" name="price_public" x-mask:dynamic="$money($input)" placeholder="0.00">
                            <x-slot name="prefix">$</x-slot>
                        </x-ui.input>
                        <x-ui.error name="form.price_public" />
                    </x-ui.field>

                    <x-ui.field required>
                        <x-ui.label>Precio Miembros</x-ui.label>
                        <x-ui.input wire:model="form.price_members" name="price_members" x-mask:dynamic="$money($input)" placeholder="0.00">
                            <x-slot name="prefix">$</x-slot>
                        </x-ui.input>
                        <x-ui.error name="form.price_members" />
                    </x-ui.field>
                </div>
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

    <livewire:medications.checkout-modal />
</div>

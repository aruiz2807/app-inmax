<div>
    <x-slot name="header">
        {{ __('app.plans') }}
    </x-slot>

    <div>
        <x-ui.card size="full">
            <x-ui.heading class="flex items-center justify-between mb-4" level="h3" size="sm">
                <span>Catalogo de coberturas</span>

                <x-ui.modal.trigger id="plan-modal" wire:click="resetForm">
                    <x-ui.button color="teal" icon="plus-circle">
                        Agregar cobertura
                    </x-ui.button>
                </x-ui.modal.trigger>
            </x-ui.heading>
            <p>Administre los planes de cobertura que pueden ser incluidos en una poliza</p>
        </x-ui.card>
    </div>

    <div class="pt-2">
        <x-ui.card size="full">
            <livewire:plans.plans-table />
        </x-ui.card>
    </div>

    <x-ui.modal
        id="plan-modal"
        animation="fade"
        width="2xl"
        heading="{{$planId ? 'Editar cobertura' : 'Nueva cobertura'}}"
        description="Ingrese la siguiente información para registrar una cobertura"
        x-on:close-plan-modal.window="$data.close()"
        x-on:open-plan-modal.window="$data.open()"
    >
        <form wire:submit="save">
            <x-ui.fieldset label="Información de la cobertura">
                <x-ui.field required>
                    <x-ui.label>Nombre</x-ui.label>
                    <x-ui.input wire:model="form.name" name="name" placeholder="Cobertura basica" />
                    <x-ui.error name="form.name" />
                </x-ui.field>

                <x-ui.field required>
                    <x-ui.label>Precio</x-ui.label>
                    <x-ui.input wire:model="form.price" name="price" x-mask:dynamic="$money($input)" placeholder="0.00">
                        <x-slot name="prefix">$</x-slot>
                    </x-ui.input>
                    <x-ui.error name="form.price" />
                </x-ui.field>

                 <x-ui.radio.group wire:model="form.type" name="type" label="Tipo de cobertura" variant="cards" direction="horizontal" class="mt-4">
                    <x-ui.radio.item
                        icon="user"
                        value="Individual"
                        label="Individual"
                        checked
                    />
                    <x-ui.radio.item
                        icon="user-group"
                        value="Group"
                        label="Colectiva"
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

    <livewire:plans.plan-benefits-modal />
</div>

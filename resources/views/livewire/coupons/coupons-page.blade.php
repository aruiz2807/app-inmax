<div>
    <x-slot name="header">
        {{ __('app.coupons') }}
    </x-slot>

    <div>
        <x-ui.card size="full">
            <x-ui.heading class="flex items-center justify-between mb-4" level="h3" size="sm">
                <span>Catalogo de cupones</span>

                <x-ui.modal.trigger id="coupon-modal" wire:click="resetForm">
                    <x-ui.button color="teal" icon="plus-circle">
                        Agregar cupón
                    </x-ui.button>
                </x-ui.modal.trigger>
            </x-ui.heading>
            <p>Administre los cupones que pueden ser incluidos en los planes</p>
        </x-ui.card>
    </div>

    <div class="pt-2">
        <x-ui.card size="full">
            <livewire:coupons.coupons-table />
        </x-ui.card>
    </div>

    <x-ui.modal
        id="coupon-modal"
        animation="fade"
        width="2xl"
        heading="{{$couponId ? 'Editar cupón' : 'Nuevo cupón'}}"
        description="Ingrese la siguiente información para registrar un cupón"
        x-on:close-coupon-modal.window="$data.close()"
        x-on:open-coupon-modal.window="$data.open()"
    >
        <form wire:submit="save">
            @csrf

            <x-ui.fieldset label="Información del cupón">
                <x-ui.radio.group wire:model="form.type" name="type" label="Tipo de cupón" variant="cards" direction="horizontal">
                    <x-ui.radio.item
                        icon="currency-dollar"
                        value="Amount"
                        label="Importe"
                        description="El cupón descuenta el importe indicado"
                        checked
                    />
                    <x-ui.radio.item
                        icon="percent-badge"
                        value="Percentage"
                        label="Porcentaje"
                        description="El cupón descuenta el porcentaje indicado"
                    />
                </x-ui.radio.group>

                <x-ui.field required>
                    <x-ui.label>Nombre</x-ui.label>
                    <x-ui.input wire:model="form.name" name="name" placeholder="Beneficio por $100 pesos en la cuenta total" />
                    <x-ui.error name="form.name" />
                </x-ui.field>

                <x-ui.field required>
                    <x-ui.label>Valor</x-ui.label>
                    <x-ui.input wire:model="form.value" name="value" x-mask:dynamic="$money($input)" placeholder="0.00">
                        <x-slot name="prefix">$</x-slot>
                    </x-ui.input>
                    <x-ui.error name="form.value" />
                </x-ui.field>

                <x-ui.field required>
                    <x-ui.label>Limite inferior</x-ui.label>
                    <x-ui.input wire:model="form.min" name="min" x-mask:dynamic="$money($input)" placeholder="0.00">
                        <x-slot name="prefix">$</x-slot>
                    </x-ui.input>
                    <x-ui.error name="form.min" />
                </x-ui.field>

                <x-ui.field required>
                    <x-ui.label>Limite superior</x-ui.label>
                    <x-ui.input wire:model="form.max" name="max" x-mask:dynamic="$money($input)" placeholder="0.00">
                        <x-slot name="prefix">$</x-slot>
                    </x-ui.input>
                    <x-ui.error name="form.max" />
                </x-ui.field>

                <x-ui.field>
                    <x-ui.label>Servicios disponibles</x-ui.label>
                    <x-ui.select
                        wire:model="form.service"
                        placeholder="Buscar servicio..."
                        icon="wallet"
                        searchable
                        clearable
                    >
                        <x-ui.select.option value="">
                            Cualquier servicio (Universal)
                        </x-ui.select.option>
                        @foreach($services as $service)
                            <x-ui.select.option value="{{ $service->id }}">
                                {{ $service->name }}
                            </x-ui.select.option>
                        @endforeach
                    </x-ui.select>
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

    <livewire:coupons.coupon-doctors-modal />
</div>

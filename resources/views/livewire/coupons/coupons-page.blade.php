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
        heading="{{ $couponId ? 'Editar cupón' : 'Nuevo cupón' }}"
        description="Ingrese la siguiente información para registrar un cupón"
        x-on:close-coupon-modal.window="$data.close()"
        x-on:open-coupon-modal.window="$data.open()"
    >
        <form wire:submit="save" class="space-y-6">
            @csrf

            {{-- 1. Tipo de cupón --}}
            <x-ui.fieldset label="Tipo de cupón">
                <x-ui.radio.group wire:model.live="form.type" name="type" variant="cards" direction="horizontal">
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
                    <x-ui.radio.item
                        icon="briefcase"
                        value="Service"
                        label="Servicio"
                        description="El cupón cubre el servicio indicado"
                    />
                </x-ui.radio.group>
            </x-ui.fieldset>

            {{-- 2. Información general --}}
            <x-ui.fieldset label="Información del cupón">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                    {{-- Nombre: ancho completo --}}
                    <div class="sm:col-span-2">
                        <x-ui.field required>
                            <x-ui.label>Nombre</x-ui.label>
                            <x-ui.input wire:model="form.name" name="name" placeholder="Beneficio por $100 pesos en la cuenta total" />
                            <x-ui.error name="form.name" />
                        </x-ui.field>
                    </div>

                    {{-- Valor / Porcentaje (no aplica para Servicio) --}}
                    @if($form->type !== 'Service')
                        <div>
                            <x-ui.field required>
                                <x-ui.label>{{ $form->type === 'Percentage' ? 'Porcentaje' : 'Valor' }}</x-ui.label>
                                <x-ui.input wire:model="form.value" name="value" x-mask:dynamic="$money($input)" placeholder="0.00">
                                    <x-slot name="prefix">{{ $form->type === 'Percentage' ? '%' : '$' }}</x-slot>
                                </x-ui.input>
                                <x-ui.error name="form.value" />
                            </x-ui.field>
                        </div>
                    @endif

                    {{-- Cobertura Inmax: ocupa todo el ancho si es Servicio (queda sola en la fila) --}}
                    <div class="{{ $form->type === 'Service' ? 'sm:col-span-2' : '' }}">
                        <x-ui.field required>
                            <x-ui.label>Porcentaje de cobertura Inmax</x-ui.label>
                            <x-ui.input wire:model="form.inmax_coverage_percent" name="inmax_coverage_percent" x-mask:dynamic="$money($input)" placeholder="0.00">
                                <x-slot name="prefix">%</x-slot>
                            </x-ui.input>
                            <x-ui.error name="form.inmax_coverage_percent" />
                        </x-ui.field>
                    </div>

                    {{-- Límites: lado a lado --}}
                    @if($form->type === 'Amount' || $form->type === 'Percentage')
                        <div>
                            <x-ui.field required>
                                <x-ui.label>Límite inferior</x-ui.label>
                                <x-ui.input wire:model="form.min" name="min" x-mask:dynamic="$money($input)" placeholder="0.00">
                                    <x-slot name="prefix">$</x-slot>
                                </x-ui.input>
                                <x-ui.error name="form.min" />
                            </x-ui.field>
                        </div>

                        <div>
                            <x-ui.field required>
                                <x-ui.label>Límite superior</x-ui.label>
                                <x-ui.input wire:model="form.max" name="max" x-mask:dynamic="$money($input)" placeholder="0.00">
                                    <x-slot name="prefix">$</x-slot>
                                </x-ui.input>
                                <x-ui.error name="form.max" />
                            </x-ui.field>
                        </div>
                    @endif

                    {{-- Servicios: ancho completo --}}
                    <div class="sm:col-span-2">
                        <x-ui.field required>
                            <x-ui.label>Servicios disponibles</x-ui.label>
                            @if($form->type === 'Amount')
                                <x-ui.select wire:key="coupon-services-multiple" wire:model="form.services" placeholder="Buscar servicios..." icon="wallet" searchable multiple clearable>
                                    @foreach($services as $service)
                                        <x-ui.select.option value="{{ $service->id }}">{{ $service->name }}</x-ui.select.option>
                                    @endforeach
                                </x-ui.select>
                                <x-ui.error name="form.services" />
                                <x-ui.error name="form.services.*" />
                            @else
                                <x-ui.select wire:key="coupon-service-single" wire:model="form.service" placeholder="Buscar servicio..." icon="wallet" searchable clearable>
                                    @foreach($services as $service)
                                        <x-ui.select.option value="{{ $service->id }}">{{ $service->name }}</x-ui.select.option>
                                    @endforeach
                                </x-ui.select>
                                <x-ui.error name="form.service" />
                            @endif
                        </x-ui.field>
                    </div>

                </div>
            </x-ui.fieldset>

            {{-- Acciones --}}
            <div class="w-full flex justify-end gap-3 pt-2 border-t border-neutral-200 dark:border-neutral-700">
                <x-ui.button x-on:click="$data.close();" icon="x-mark" variant="outline" class="mt-4">
                    Cancelar
                </x-ui.button>

                <x-ui.button type="submit" icon="check" variant="primary" color="teal" class="mt-4">
                    Guardar
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>

    <livewire:coupons.coupon-doctors-modal />
</div>

<div
    x-data
    x-on:payment-completed.window="
        window.open($event.detail.ticketUrl, '_blank');
        window.location.href = $event.detail.redirectUrl;
    "
>
    <x-slot name="header">
        Pago de consulta
    </x-slot>

    <div class="grid grid-cols-[2rem_auto] items-center pt-4 pb-4">
        <x-ui.icon name="arrow-left" class="w-5 h-5 cursor-pointer" x-on:click="window.history.back()" />
        <x-ui.text class="text-2xl">Cierre de cuenta</x-ui.text>
    </div>

    <x-ui.card size="full" class="mx-auto">
        <x-ui.heading class="flex mb-4" level="h3" size="sm">
            <x-ui.icon name="calendar" class="self-center" />
            <x-ui.text class="text-lg ml-2">{{ $appointment->formatted_date }}</x-ui.text>
        </x-ui.heading>

        <div class="flex mt-2">
            <x-ui.avatar size="lg" icon="user" color="teal" :src="$appointment->user->photo_url" circle />
            <div class="pl-4">
                <x-ui.text class="pt-1 text-lg">{{ $appointment->user->name }}</x-ui.text>
                <x-ui.text class="text-sm opacity-75">{{ $appointment->user->policy?->number ?? 'Sin poliza' }}</x-ui.text>
            </div>
        </div>

        <div class="flex mt-2">
            <x-ui.avatar size="lg" icon="user" color="teal" :src="$appointment->doctor?->user?->photo_url" circle />
            <div class="pl-4">
                <x-ui.text class="pt-1 text-lg">{{ $appointment->doctor?->user?->name ?? 'Proveedor asignado' }}</x-ui.text>
                <x-ui.text class="text-sm opacity-75">{{ $appointment->doctor?->specialty?->name ?? $appointment->doctor?->type?->label() }}</x-ui.text>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card size="full" class="mx-auto mt-2">
        <x-ui.heading class="flex pb-2" level="h3" size="sm">
            <x-ui.icon name="clipboard-document-list" class="self-center" />
            <x-ui.text class="text-base ml-2">Servicios completados</x-ui.text>
        </x-ui.heading>

        @php
            $completedServices = $appointment->services->filter(fn ($service) => $service->status === 'Completed');
        @endphp

        @if($completedServices->isEmpty())
            <x-ui.text class="text-sm text-neutral-500">No hay servicios completados en esta cita.</x-ui.text>
        @else
            <div class="flex flex-col w-full gap-2">
                @foreach($completedServices as $service)
                    <div class="flex p-2 bg-[#FFFFFF] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                        <x-ui.avatar size="xl" icon="user" color="teal" src="/img/checkup.png" circle />

                        <div class="flex flex-col w-full">
                            <div class="flex items-center justify-between pl-4 pb-2">
                                <x-ui.text class="text-base pr-1">{{ $service->name ?? 'Servicio' }}</x-ui.text>
                                <x-ui.badge :icon="$service->covered_icon" variant="outline" :color="$service->covered_color" pill>
                                    {{ $service->covered_text }}
                                </x-ui.badge>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </x-ui.card>

    <x-ui.card size="full" class="mx-auto mt-2">
            <x-ui.heading class="flex pb-2" level="h3" size="sm">
                <x-ui.icon name="clipboard-document-list" class="self-center" />
                <x-ui.text class="text-base ml-2">Cierre de cuenta</x-ui.text>
            </x-ui.heading>

            <x-ui.field>
                <x-ui.label>Monto total de la cuenta</x-ui.label>
                <x-ui.input
                    wire:model.live="subtotal"
                    name="subtotal" x-mask:dynamic="$money($input)"
                    placeholder="0.00"
                >
                    <x-slot name="prefix">$</x-slot>
                </x-ui.input>
            </x-ui.field>

            @if($hasCouponAvailable)
            <div class="mt-4 mb-4">
                <x-ui.label class="mb-2 block text-teal-900 font-semibold">Cupones disponibles</x-ui.label>
                
                <div class="space-y-2">
                    <!-- Option to not use any coupon -->
                    <label class="flex items-center gap-3 p-3 rounded-lg border cursor-pointer transition-colors hover:bg-gray-50 {{ empty($selectedCouponId) ? 'border-teal-500 bg-teal-50/50' : 'border-gray-200 bg-white' }}">
                        <input type="radio" wire:model.live="selectedCouponId" name="selectedCouponId" value="" class="text-teal-600 focus:ring-teal-500">
                        <span class="text-sm text-gray-700">No aplicar cupón</span>
                    </label>

                    <!-- Available coupons list -->
                    @foreach($availableCoupons as $benefit)
                        <label class="flex items-start gap-3 p-3 rounded-lg border cursor-pointer transition-colors hover:bg-teal-50/30 {{ $selectedCouponId == $benefit->id ? 'border-teal-500 bg-teal-50' : 'border-teal-100 bg-white' }}">
                            <div class="pt-1">
                                <input type="radio" wire:model.live="selectedCouponId" name="selectedCouponId" value="{{ $benefit->id }}" class="text-teal-600 focus:ring-teal-500">
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <x-ui.icon name="ticket" class="w-4 h-4 {{ $selectedCouponId == $benefit->id ? 'text-teal-600' : 'text-teal-400' }}" />
                                    <p class="font-bold {{ $selectedCouponId == $benefit->id ? 'text-teal-900' : 'text-gray-900' }}">
                                        {{ $benefit->coupon->name }}
                                    </p>
                                </div>
                                <p class="text-xs mt-1 {{ $selectedCouponId == $benefit->id ? 'text-teal-700' : 'text-gray-500' }}">
                                    @if($benefit->coupon->type === 'Amount')
                                        Descuento de ${{ number_format($benefit->coupon->value, 2) }}
                                    @else
                                        {{ $benefit->coupon->value }}% de descuento
                                    @endif
                                </p>
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>
            @endif

            @if($couponDiscountValue > 0)
            <x-ui.field class="mt-2">
                <x-ui.label>Descuento por cupón</x-ui.label>
                <x-ui.alerts variant="success" icon="ticket">
                    <x-ui.alerts.heading>-{{$couponDiscountValue}}</x-ui.alerts.heading>
                </x-ui.alerts>
            </x-ui.field>
            @endif

            <x-ui.field class="mt-2">
                <x-ui.label>Cobro al paciente (Pago miembro)</x-ui.label>
                <x-ui.alerts variant="success" icon="currency-dollar">
                    <x-ui.alerts.heading>{{$user_payment}}</x-ui.alerts.heading>
                </x-ui.alerts>
            </x-ui.field>

            <x-ui.field class="mt-2">
                <x-ui.label>Comision Inmax</x-ui.label>
                <x-ui.alerts variant="info" icon="currency-dollar">
                    <x-ui.alerts.description>{{$commision}}</x-ui.alerts.description>
                </x-ui.alerts>
            </x-ui.field>

            <x-ui.field class="mt-2">
                <x-ui.label>Ganancia del socio</x-ui.label>
                <x-ui.alerts variant="info" icon="currency-dollar">
                    <x-ui.alerts.description>{{$total}}</x-ui.alerts.description>
                </x-ui.alerts>
            </x-ui.field>
        </x-ui.card>

    <x-ui.card size="full" class="mx-auto mt-2">
        <x-ui.heading class="flex pb-2" level="h3" size="sm">
            <x-ui.icon name="credit-card" class="self-center" />
            <x-ui.text class="text-base ml-2">Informacion del pago</x-ui.text>
        </x-ui.heading>

        <x-ui.field required>
            <x-ui.label>Metodo de pago</x-ui.label>
            <x-ui.select
                placeholder="Seleccione el metodo de pago..."
                icon="wallet"
                wire:model="payment_method"
            >
                <x-ui.select.option value="CS">Efectivo</x-ui.select.option>
                <x-ui.select.option value="CC">Tarjeta de credito</x-ui.select.option>
                <x-ui.select.option value="DC">Tarjeta de debito</x-ui.select.option>
                <x-ui.select.option value="TR">Transferencia</x-ui.select.option>
                <x-ui.select.option value="SI">Servicios incluidos</x-ui.select.option>
            </x-ui.select>
            <x-ui.error name="payment_method" />
        </x-ui.field>

        <x-ui.field class="mt-2">
            <x-ui.label>Referencia</x-ui.label>
            <x-ui.input wire:model="payment_reference" name="payment_reference" placeholder="Referencia de pago" />
            <x-ui.error name="payment_reference" />
        </x-ui.field>

        <x-ui.field class="mt-2">
            <x-ui.label>Comprobante</x-ui.label>
            <input type="file" wire:model="payment_attachment" placeholder="Seleccione un archivo para adjuntar" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200"/>
            <x-ui.error name="payment_attachment" />
            <div wire:loading wire:target="payment_attachment" class="text-sm text-neutral-500 mt-2">
                Subiendo archivo...
            </div>
        </x-ui.field>
    </x-ui.card>

    <div class="flex justify-center mt-4">
        <x-ui.button class="w-40 mr-1" wire:click="save" variant="outline" color="blue" icon="banknotes" :disabled="$paymentSaved">
            {{ $paymentSaved ? 'Pago guardado' : 'Guardar pago' }}
        </x-ui.button>
    </div>

    <x-ui.modal
        id="payment-modal"
        animation="fade"
        width="md"
        heading="Confirmar pago"
        description="Desea guardar el cierre de cuenta de esta consulta?"
        x-on:open-payment-modal.window="$data.open()"
        x-on:close-payment-modal.window="$data.close()"
    >
        <div class="flex justify-end gap-3 pt-4">
            <x-ui.button x-on:click="$data.close()" icon="x-mark" variant="outline">
                Cancelar
            </x-ui.button>

            <x-ui.button color="teal" icon="check" wire:click="confirmPayment">
                Confirmar
            </x-ui.button>
        </div>
    </x-ui.modal>
</div>
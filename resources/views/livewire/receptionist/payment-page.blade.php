<div>
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
            <x-ui.icon name="banknotes" class="self-center" />
            <x-ui.text class="text-base ml-2">Cierre de cuenta</x-ui.text>
        </x-ui.heading>

        @if($paymentSaved)
            <div class="mb-4">
                <x-ui.alerts variant="success" icon="check-circle">
                    <x-ui.alerts.heading>{{ $paymentSuccessMessage }}</x-ui.alerts.heading>
                </x-ui.alerts>
            </div>
        @endif

        @if($hasCouponAvailable)
        <div class="flex items-center justify-between p-4 bg-teal-50 rounded-xl border border-teal-100 mb-4">
            <div class="flex items-center gap-3">
                <x-ui.icon name="ticket" class="w-6 h-6 text-teal-600" />
                <div>
                    <p class="font-bold text-teal-900">Cupón disponible</p>
                    <p class="text-xs text-teal-700">
                        {{ $availableCouponBenefit->doctorCoupon->coupon->name }}
                        @if($availableCouponBenefit->doctorCoupon->coupon->type === 'Amount')
                            (${{ number_format($availableCouponBenefit->doctorCoupon->coupon->value, 2) }})
                        @else
                            ({{ $availableCouponBenefit->doctorCoupon->coupon->value }}%)
                        @endif
                    </p>
                </div>
            </div>
            <x-ui.switch wire:key="coupon-switch-{{ $useCoupon ? '1' : '0' }}" wire:model.live="useCoupon" :checked="$useCoupon" color="teal" />
        </div>
        @endif

        <x-ui.field>
            <x-ui.label>Monto total de la cuenta</x-ui.label>
            <x-ui.input
                wire:model.live="subtotal"
                name="subtotal" x-mask:dynamic="$money($input)"
                placeholder="0.00"
            >
                <x-slot name="prefix">$</x-slot>
            </x-ui.input>
            <x-ui.error name="subtotal" />
        </x-ui.field>

        @if($couponDiscountValue > 0)
        <x-ui.field class="mt-2">
            <x-ui.label>Descuento por cupón</x-ui.label>
            <x-ui.alerts variant="success" icon="ticket">
                <x-ui.alerts.heading>-{{ $couponDiscountValue }}</x-ui.alerts.heading>
            </x-ui.alerts>
        </x-ui.field>
        @endif

        <x-ui.field class="mt-2">
            <x-ui.label>Cobro al paciente (Pago miembro)</x-ui.label>
            <x-ui.alerts variant="success" icon="currency-dollar">
                <x-ui.alerts.heading>{{ $user_payment }}</x-ui.alerts.heading>
            </x-ui.alerts>
        </x-ui.field>

        <x-ui.field class="mt-2">
            <x-ui.label>Comision Inmax</x-ui.label>
            <x-ui.alerts variant="info" icon="currency-dollar">
                <x-ui.alerts.description>{{ $commision }}</x-ui.alerts.description>
            </x-ui.alerts>
        </x-ui.field>

        <x-ui.field class="mt-2">
            <x-ui.label>Ganancia del proveedor</x-ui.label>
            <x-ui.alerts variant="info" icon="currency-dollar">
                <x-ui.alerts.description>{{ $total }}</x-ui.alerts.description>
            </x-ui.alerts>
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
<div>
    <x-ui.modal
        id="checkout-modal"
        animation="fade"
        width="2xl"
        heading="Surtir medicamentos"
        description="Seleccione el beneficio que usara el cliente"
        x-on:close-checkout-modal.window="$data.close()"
        x-on:open-checkout-modal.window="$data.open()"
    >
        @if($user)
        <div class="flex flex-col items-center mb-6">
            <x-ui.avatar size="xl" icon="user" color="teal" :src="$user->photo_url" circle />
            <x-ui.text class="pt-2 pb-2 text-xl">{{$user->name}}</x-ui.text>
            <x-ui.badge :icon="$user->policy->status_icon" variant="outline" :color="$user->policy->status_color" pill>
                {{$user->policy->status_text}}
            </x-ui.badge>
        </div>
        @endif

        @if(count($prescriptions) > 0)
        <div class="mt-4">
            <x-ui.heading class="flex pb-2" level="h3" size="sm">
                <x-ui.icon name="clipboard-document-list" class="self-center" />
                <x-ui.text class="text-base ml-2">Medicamentos recetados</x-ui.text>
            </x-ui.heading>
            
            <div class="flex flex-col gap-2">
                @foreach($prescriptions as $prescription)
                    <div class="bg-gray-50 p-3 rounded-xl border border-gray-100 flex justify-between items-center gap-4">
                        <div class="flex-1">
                            <x-ui.text class="font-bold text-base">{{ $prescription->medication->name }} ({{ $prescription->medication->trade_name }})</x-ui.text>
                            <x-ui.text class="text-sm text-gray-600">
                                {{ $prescription->quantity }} • {{ $prescription->dose }} • {{ $prescription->frequency }} • {{ $prescription->duration }}
                            </x-ui.text>
                        </div>
                        <div class="flex items-center gap-4">
                            <div class="w-24">
                                <x-ui.input type="number" min="0" wire:model.live="deliveryQuantities.{{ $prescription->id }}" />
                            </div>
                            <div class="text-right min-w-[5rem]">
                                <x-ui.text class="font-bold text-lg text-teal-600">${{ number_format(($deliveryQuantities[$prescription->id] ?? 0) * $prescription->medication->price_public, 2) }}</x-ui.text>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex flex-col gap-2 mt-4">
                @if($hasCouponAvailable)
                <div class="flex items-center justify-between p-4 bg-teal-50 rounded-xl border border-teal-100 mb-2">
                    <div class="flex items-center gap-3">
                        <x-ui.icon name="ticket" class="w-6 h-6 text-teal-600" />
                        <div>
                            <p class="font-bold text-teal-900">Cupón de descuento disponible</p>
                            <p class="text-sm text-teal-700">Aplicar cupón de descuento de ${{ number_format($couponValue, 2) }}</p>
                        </div>
                    </div>
                    <x-ui.switch wire:key="coupon-switch-{{ $useCoupon ? '1' : '0' }}" wire:model.live="useCoupon" :checked="$useCoupon" color="teal" />
                </div>
                @endif

                @if($isMembershipActive)
                <div class="flex items-center justify-between p-4 bg-blue-50 rounded-xl border border-blue-100 mb-2">
                    <div class="flex items-center gap-3">
                        <x-ui.icon name="shield-check" class="w-6 h-6 text-blue-600" />
                        <div>
                            <p class="font-bold text-blue-900">Precio preferencial para miembros</p>
                            <p class="text-sm text-blue-700">Beneficio por membresía activa</p>
                        </div>
                    </div>
                    <x-ui.switch wire:key="discount-switch-{{ $useMembersDiscount ? '1' : '0' }}" wire:model.live="useMembersDiscount" :checked="$useMembersDiscount" color="blue" />
                </div>
                @endif

                <div class="flex justify-end mt-4 pt-4 border-t border-gray-200 flex-col items-end">
                    @php
                        $subtotalPublic = 0;
                        $subtotalMembers = 0;
                        foreach ($prescriptions as $prescription) {
                            $qty = $deliveryQuantities[$prescription->id] ?? 0;
                            $subtotalPublic += $qty * $prescription->medication->price_public;
                            $subtotalMembers += $qty * $prescription->medication->price_members;
                        }
                        
                        $discount = 0;
                        if ($useMembersDiscount && $isMembershipActive) {
                            $discount = $subtotalPublic - $subtotalMembers;
                        } elseif ($useCoupon && $hasCouponAvailable) {
                            $discount = $couponValue;
                        }
                    @endphp

                    @if($discount > 0)
                    <p class="text-sm text-gray-500 line-through">Subtotal: ${{ number_format($subtotalPublic, 2) }}</p>
                    <p class="text-sm {{ $useCoupon ? 'text-teal-600' : 'text-blue-600' }}">
                        Descuento: -${{ number_format($discount, 2) }}
                    </p>
                    @endif
                    <p class="text-xl font-bold">Total a pagar: <span class="text-teal-600">${{ number_format($this->total, 2) }}</span></p>
                </div>
                
                <div class="flex justify-end gap-3 mt-4 pt-4 border-t border-gray-200">
                    <x-ui.button x-on:click="$data.close();" icon="x-mark" variant="outline">
                        Cancelar
                    </x-ui.button>

                    <x-ui.button wire:click="dispense" icon="check" variant="primary" color="teal" :disabled="!$this->canDispense">
                        Surtir
                    </x-ui.button>
                </div>
            </div>
        </div>
        @endif

    </x-ui.modal>
</div>
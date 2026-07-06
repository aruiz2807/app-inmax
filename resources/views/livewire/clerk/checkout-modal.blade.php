<div x-on:print-checkout-ticket.window="$wire.print_ticket()">
    <x-ui.modal
        id="checkout-modal"
        animation="fade"
        width="3xl"
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
                    @php 
                        $isDispensed = $prescription->status === 'Dispensed'; 
                        $isManual = is_null($prescription->medication_id);
                        $hasRequiredQuantity = !is_null($prescription->required_quantity);
                    @endphp

                    <div 
                        class="bg-gray-50 p-3 rounded-xl border flex justify-between items-center gap-4
                            {{ $isDispensed ? 'opacity-50 border-gray-300' : 'border-gray-100' }}"
                        aria-disabled="{{ $isDispensed ? 'true' : 'false' }}"
                    >
                        <div class="flex-1">
                            <x-ui.text class="font-bold text-base">
                                {{ $isManual ? $prescription->description : $prescription->medication->name . ' (' . $prescription->medication->trade_name . ')' }}
                                @if($prescription->medication?->existences > 0)
                                    <x-ui.badge color="green" pill size="sm">
                                        {{ (int) $prescription->medication?->existences }}
                                    </x-ui.badge>
                                @else
                                    <x-ui.badge color="red" pill size="sm">
                                        0
                                    </x-ui.badge>
                                @endif
                            </x-ui.text>

                            <x-ui.text class="text-sm text-gray-600">
                                {{ $prescription->quantity }} • {{ $prescription->dose }} • cada {{ $prescription->frequency }} • durante {{ $prescription->duration }}
                            </x-ui.text>
                            
                            @if($isDispensed)
                                <x-ui.text class="text-xs text-gray-500 mt-1">
                                    Surtida
                                </x-ui.text>
                            @elseif($isManual)
                                <x-ui.text class="text-xs text-amber-600 mt-1">
                                    Medicamento no disponible en catálogo
                                </x-ui.text>
                            @endif
                        </div>

                        @if(!$isManual)
                        <div class="flex items-center gap-4">
                            <div class="flex items-center gap-2 w-60">
                                <div class="w-20">
                                    <span class="block text-xs text-gray-500 mb-1">{{ $this->isPartialDispensation ? 'Pendiente' : 'Surtido' }}</span>
                                    <x-ui.input 
                                        type="number" 
                                        min="0"
                                        :max="$hasRequiredQuantity ? max(0, ((int) $prescription->required_quantity) - ((int) ($prescription->delivered_quantity ?? 0))) : null"
                                        wire:model.live="deliveryQuantities.{{ $prescription->id }}"
                                        :disabled="$isDispensed"
                                    />
                                </div>
                                
                                @if(!$hasRequiredQuantity)
                                    <span>/</span>
                                    <div class="w-20">
                                        <span class="block text-xs text-gray-500 mb-1">Total</span>
                                        <x-ui.input 
                                            type="number" 
                                            min="0"
                                            value="1"
                                            wire:model.live="requiredQuantities.{{ $prescription->id }}"  
                                        />
                                    </div>

                                    <div class="text-right min-w-20">
                                        <x-ui.text class="font-bold text-lg text-teal-600">
                                            ${{ number_format(((int) ($requiredQuantities[$prescription->id] ?? 0)) * $prescription->medication->price_public, 2) }}
                                        </x-ui.text>
                                    </div>
                                @else
                                    <div class="w-20 self-start">
                                        <span class="block text-xs text-gray-500 mb-1">Surtido/Total</span>
                                        <span>{{ $prescription->delivered_quantity }}/{{ $prescription->required_quantity }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="flex flex-col gap-2 mt-4">
                @if($hasCouponAvailable && $this->showCheckoutBenefits)
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

                @if($isMembershipActive && $this->showCheckoutBenefits)
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
                            if (!is_null($prescription->medication_id) && $prescription->status === 'Prescribed') {
                                $qty = (int) ($requiredQuantities[$prescription->id] ?? 0);
                                $subtotalPublic += $qty * $prescription->medication->price_public;
                                $subtotalMembers += $qty * $prescription->medication->price_members;
                            }
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

                    @if ($this->showCheckoutBenefits)
                        <p class="text-xl font-bold">Total a pagar: <span class="text-teal-600">${{ number_format($this->total, 2) }}</span></p>
                    @endif
                </div>
                
                <div class="flex justify-end gap-3 mt-4 pt-4 border-t border-gray-200">
                    <x-ui.button x-on:click="$data.close();" icon="x-mark" variant="outline">
                        Cancelar
                    </x-ui.button>

                    @if($this->showDispenseAction)
                        <x-ui.button wire:click="dispense" icon="check" variant="primary" color="teal" :disabled="!$this->canDispense">
                            Surtir
                        </x-ui.button>
                    @endif
                </div>
            </div>
        </div>
        @endif

    </x-ui.modal>
</div>
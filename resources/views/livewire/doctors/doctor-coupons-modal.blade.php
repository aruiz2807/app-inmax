<div>
    <x-ui.modal
    id="doctor-coupons-modal"
    animation="fade"
    width="2xl"
    heading="Incluir cupones"
    description="Seleccione los cupones que otorga esta medico/proveedor"
    x-on:close-doctor-coupons-modal.window="$data.close()"
    x-on:open-doctor-coupons-modal.window="$data.open()"
    >
        <div class="flex items-end gap-4">
            <x-ui.field>
                <x-ui.label>Cupones disponibles</x-ui.label>
                <x-ui.select
                    wire:model="couponId"
                    placeholder="Buscar cupón..."
                    icon="ticket"
                    searchable
                >
                    @foreach($coupons as $coupon)
                        <x-ui.select.option value="{{ $coupon->id }}">
                            {{ $coupon->name }}
                        </x-ui.select.option>
                    @endforeach
                </x-ui.select>
            </x-ui.field>

            <x-ui.button wire:click="addCoupon" icon="arrow-down-tray" variant="primary" color="teal">
                Incluir
            </x-ui.button>
        </div>

        <form wire:submit.prevent="updateCoupons">
            <x-ui.fieldset label="Cupones otorgados" class="mt-4">
                <table class="border-separate border-spacing-y-2">
                    <thead>
                        <tr>
                            <th>
                                <x-ui.text class="font-semibold">Cupón</x-ui.text>
                            </th>
                            <th>
                                <x-ui.text class="font-semibold">Tipo</x-ui.text>
                            </th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($doctorCoupons as $doctorCoupon)
                            <tr wire:key="service-{{ $doctorCoupon->id }}">
                                <td>
                                    <x-ui.text>
                                        {{ $doctorCoupon->coupon->name }}
                                    </x-ui.text>
                                </td>

                                <td>
                                    <x-ui.text>
                                        {{ $doctorCoupon->coupon->type === 'Amount' ? 'Importe' : 'Porcentaje' }}
                                    </x-ui.text>
                                </td>

                                <td class="pl-4">
                                    <x-ui.button wire:click="delete({{ $doctorCoupon->id }})" type="button" icon="trash" variant="danger" size="sm"/>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

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

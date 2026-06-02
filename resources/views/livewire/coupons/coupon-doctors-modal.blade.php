<div>
    <x-ui.modal
    id="coupon-doctors-modal"
    animation="fade"
    width="2xl"
    heading="Incluir proveedores"
    description="Seleccione los proveedores que pueden otorgar este cupon"
    x-on:close-coupon-doctors-modal.window="$data.close()"
    x-on:open-coupon-doctors-modal.window="$data.open()"
    >
        <div class="flex items-end gap-4">
            <x-ui.field>
                <x-ui.label>Proveedores disponibles</x-ui.label>
                <x-ui.select
                    wire:model="doctorId"
                    placeholder="Buscar proveedor..."
                    icon="ticket"
                    searchable
                >
                    @foreach($doctors as $doctor)
                        <x-ui.select.option value="{{ $doctor->id }}">
                            {{ $doctor->user->name }}
                        </x-ui.select.option>
                    @endforeach
                </x-ui.select>
            </x-ui.field>

            <x-ui.button wire:click="addDoctor" icon="arrow-down-tray" variant="primary" color="teal">
                Incluir
            </x-ui.button>
        </div>

        <form wire:submit.prevent="updateDoctors">
            <x-ui.fieldset label="Proveedores asignados" class="mt-4">
                <table class="border-separate border-spacing-y-2">
                    <thead>
                        <tr>
                            <th>
                                <x-ui.text class="font-semibold">Doctor</x-ui.text>
                            </th>
                            <th>
                                <x-ui.text class="font-semibold">Tipo</x-ui.text>
                            </th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($couponDoctors as $couponDoctor)
                            <tr wire:key="coupon-doctor-{{ $couponDoctor->id }}">
                                <td>
                                    <x-ui.text>
                                        {{ $couponDoctor->doctor?->user?->name ?? 'N/A' }}
                                    </x-ui.text>
                                </td>

                                <td>
                                    <x-ui.text>
                                        {{ $couponDoctor->doctor?->type ?? 'N/A' }}
                                    </x-ui.text>
                                </td>

                                <td class="pl-4">
                                    <x-ui.button wire:click="delete({{ $couponDoctor->id }})" type="button" icon="trash" variant="danger" size="sm"/>
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
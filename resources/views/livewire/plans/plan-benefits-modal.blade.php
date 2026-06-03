<div>
    <x-ui.modal
    id="plan-benefits-modal"
    animation="fade"
    width="2xl"
    heading="Incluir beneficios"
    description="Seleccione los beneficios que incluira el plan"
    x-on:close-plan-benefits-modal.window="$data.close()"
    x-on:open-plan-benefits-modal.window="$data.open()"
    >
        <div class="flex items-end gap-4">
            <x-ui.field>
                <x-ui.label>Cupones disponibles</x-ui.label>
                <x-ui.select
                    wire:model="selectedBenefitId"
                    placeholder="Buscar cupón..."
                    icon="ticket"
                    searchable
                >
                    @foreach($availableCoupons as $item)
                        <x-ui.select.option value="{{ $item->id }}">
                            {{ $item->name }}
                        </x-ui.select.option>
                    @endforeach
                </x-ui.select>
            </x-ui.field>
                
            <x-ui.button wire:click="addBenefit" icon="arrow-down-tray" variant="primary" color="teal">
                Incluir
            </x-ui.button>
        </div>

        <form wire:submit.prevent="updateBenefits">
            <x-ui.fieldset label="Beneficios incluidos" class="mt-4">
                <table class="w-full border-separate border-spacing-y-2">
                    <thead>
                        <tr class="text-left">
                            <th class="pb-2">
                                <x-ui.text class="font-semibold">Beneficio</x-ui.text>
                            </th>
                            <th class="pb-2">
                                <x-ui.text class="font-semibold">Tipo</x-ui.text>
                            </th>
                            <th class="pb-2 pl-4 w-48">
                                <x-ui.text class="font-semibold">Cantidad</x-ui.text>
                            </th>
                            <th class="pb-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($benefits as $benefit)
                            <tr wire:key="benefit-{{ $benefit->id }}">
                                <td>
                                    <div class="flex flex-col">
                                        <x-ui.text class="font-medium"> {{ $benefit->coupon->name }} </x-ui.text>
                                    </div>
                                </td>

                                <td>
                                    <x-ui.text> Cupón </x-ui.text>
                                </td>

                                <td class="pl-4">
                                    <x-ui.field>
                                        <x-ui.input wire:model.defer="events.{{ $benefit->id }}" type="number" min="0" placeholder="0"/>
                                    </x-ui.field>
                                </td>

                                <td class="pl-4">
                                    <x-ui.button wire:click="delete({{ $benefit->id }})" type="button" icon="trash" variant="danger" size="sm"/>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

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
</div>

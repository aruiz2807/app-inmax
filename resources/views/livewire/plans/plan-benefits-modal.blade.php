<div>
    <x-ui.modal
    id="plan-benefits-modal"
    animation="fade"
    width="2xl"
    heading="Incluir beneficios"
    description="Seleccione los beneficios que incluira la cobertura"
    x-on:close-plan-benefits-modal.window="$data.close()"
    x-on:open-plan-benefits-modal.window="$data.open()"
    >
        <div class="flex items-end gap-4">
            <x-ui.field>
                <x-ui.label>Servicios disponibles</x-ui.label>
                <x-ui.select
                    wire:model="serviceId"
                    placeholder="Buscar servicio..."
                    icon="wallet"
                    searchable
                >
                    @foreach($services as $service)
                        <x-ui.select.option value="{{ $service->id }}">
                            {{ $service->name }}
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
                <table class="border-separate border-spacing-y-2">
                    <thead>
                        <tr>
                            <th>
                                <x-ui.text class="font-semibold">Servicio</x-ui.text>
                            </th>
                            <th>
                                <x-ui.text class="font-semibold">Tipo</x-ui.text>
                            </th>
                            <th class="pl-4">
                                <x-ui.text class="font-semibold">Cantidad/Valor</x-ui.text>
                            </th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($benefits as $benefit)
                            <tr wire:key="benefit-{{ $benefit->id }}">
                                <td>
                                    <x-ui.text>
                                        {{ $benefit->service->name }}
                                    </x-ui.text>
                                </td>

                                <td>
                                    <x-ui.text>
                                        {{ $benefit->service->type === 'Amount' ? 'Importe' : 'Evento' }}
                                    </x-ui.text>
                                </td>

                                <td class="pl-4">
                                @if($benefit->service->type === 'Amount')
                                    <x-ui.field>
                                        <x-ui.input wire:model.defer="values.{{ $benefit->id }}" x-mask:dynamic="$money($input)" placeholder="0.00">
                                            <x-slot name="prefix">$</x-slot>
                                        </x-ui.input>
                                    </x-ui.field>
                                @elseif($benefit->service->type === 'Event')
                                    <x-ui.field>
                                        <x-ui.input wire:model.defer="values.{{ $benefit->id }}" type="number" min="0"/>
                                    </x-ui.field>
                                @endif
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
                    Cancel
                </x-ui.button>

                <x-ui.button type="submit" icon="check" variant="primary" color="teal">
                    Guardar
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>

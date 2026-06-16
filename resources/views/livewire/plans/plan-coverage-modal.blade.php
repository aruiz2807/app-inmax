<div>
    <x-ui.modal
    id="plan-coverage-modal"
    animation="fade"
    width="2xl"
    heading="Incluir servicios"
    description="Seleccione los servicios inmax que incluira el plan"
    x-on:close-plan-coverage-modal.window="$data.close()"
    x-on:open-plan-coverage-modal.window="$data.open()"
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

            <x-ui.button wire:click="addCoverage" icon="arrow-down-tray" variant="primary" color="teal">
                Incluir
            </x-ui.button>
        </div>

        <form wire:submit.prevent="updateCoverage">
            <x-ui.fieldset label="Servicios incluidos" class="mt-4">
                <table class="w-full border-separate border-spacing-y-2">
                    <thead>
                        <tr class="text-left">
                            <th class="pb-2">
                                <x-ui.text class="font-semibold">Servicio</x-ui.text>
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
                        @foreach($coverage as $included)
                            <tr wire:key="coverage-{{ $included->id }}">
                                <td>
                                    <div class="flex flex-col">
                                        <x-ui.text class="font-medium"> {{ $included->service?->name }} </x-ui.text>
                                    </div>
                                </td>

                                <td>
                                    <x-ui.text> Servicio </x-ui.text>
                                </td>

                                <td class="pl-4">
                                @if($included->service?->type === 'Amount')
                                    <x-ui.field>
                                        <x-ui.input wire:model.defer="values.{{ $included->id }}" x-mask:dynamic="$money($input)" placeholder="0.00">
                                            <x-slot name="prefix">$</x-slot>
                                        </x-ui.input>
                                    </x-ui.field>
                                @elseif($included->service?->type === 'Event')
                                    <x-ui.field>
                                        <x-ui.input wire:model.defer="values.{{ $included->id }}" type="number" min="0"/>
                                    </x-ui.field>
                                @endif
                                </td>

                                <td class="pl-4">
                                    <x-ui.button wire:click="delete({{ $included->id }})" type="button" icon="trash" variant="danger" size="sm"/>
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

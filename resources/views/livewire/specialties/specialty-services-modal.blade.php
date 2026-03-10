<div>
    <x-ui.modal
    id="specialty-services-modal"
    animation="fade"
    width="2xl"
    heading="Incluir servicios"
    description="Seleccione los servicios que otorga esta especialidad"
    x-on:close-specialty-services-modal.window="$data.close()"
    x-on:open-specialty-services-modal.window="$data.open()"
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

            <x-ui.button wire:click="addService" icon="arrow-down-tray" variant="primary" color="teal">
                Incluir
            </x-ui.button>
        </div>

        <form wire:submit.prevent="updateServices">
            <x-ui.fieldset label="Servicios otorgados" class="mt-4">
                <table class="border-separate border-spacing-y-2">
                    <thead>
                        <tr>
                            <th>
                                <x-ui.text class="font-semibold">Servicio</x-ui.text>
                            </th>
                            <th>
                                <x-ui.text class="font-semibold">Tipo</x-ui.text>
                            </th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($specialtyServices as $specialtyService)
                            <tr wire:key="service-{{ $specialtyService->id }}">
                                <td>
                                    <x-ui.text>
                                        {{ $specialtyService->service->name }}
                                    </x-ui.text>
                                </td>

                                <td>
                                    <x-ui.text>
                                        {{ $specialtyService->service->type === 'Amount' ? 'Importe' : 'Evento' }}
                                    </x-ui.text>
                                </td>

                                <td class="pl-4">
                                    <x-ui.button wire:click="delete({{ $specialtyService->id }})" type="button" icon="trash" variant="danger" size="sm"/>
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

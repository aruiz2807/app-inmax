<div>
    <x-ui.modal
    id="plan-benefits-modal"
    animation="fade"
    width="4xl"
    heading="Incluir beneficios"
    description="Seleccione los beneficios que incluira el plan"
    x-on:close-plan-benefits-modal.window="$data.close()"
    x-on:open-plan-benefits-modal.window="$data.open()"
    >
        <div class="flex flex-col gap-4">
            <div class="flex gap-4 items-center">
                <x-ui.label>¿Qué desea agregar?</x-ui.label>
                <div class="flex gap-2">
                    <x-ui.button 
                        wire:click="setBenefitType('Service')" 
                        variant="{{ $benefitType === 'Service' ? 'primary' : 'outline' }}" 
                        color="{{ $benefitType === 'Service' ? 'teal' : 'slate' }}" 
                        size="sm"
                    >
                        Servicio de Doctor
                    </x-ui.button>
                    <x-ui.button 
                        wire:click="setBenefitType('Coupon')" 
                        variant="{{ $benefitType === 'Coupon' ? 'primary' : 'outline' }}" 
                        color="{{ $benefitType === 'Coupon' ? 'teal' : 'slate' }}" 
                        size="sm"
                    >
                        Cupón de Doctor
                    </x-ui.button>
                </div>
            </div>

            <div class="flex items-end gap-4">
                @if($benefitType === 'Service')
                    <x-ui.field class="flex-1">
                        <x-ui.label>Servicios disponibles</x-ui.label>
                        <x-ui.select
                            wire:model="selectedBenefitId"
                            placeholder="Buscar servicio..."
                            icon="wallet"
                            searchable
                        >
                            @foreach($availableServices as $item)
                                <x-ui.select.option value="{{ $item->id }}">
                                    {{ $item->service->name }} ({{ $item->doctor->user->name }})
                                </x-ui.select.option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>
                @endif

                @if($benefitType === 'Coupon')
                    <x-ui.field class="flex-1">
                        <x-ui.label>Cupones disponibles</x-ui.label>
                        <x-ui.select
                            wire:model="selectedBenefitId"
                            placeholder="Buscar cupón..."
                            icon="ticket"
                            searchable
                        >
                            @foreach($availableCoupons as $item)
                                <x-ui.select.option value="{{ $item->id }}">
                                    {{ $item->coupon->name }} ({{ $item->doctor->user->name }})
                                </x-ui.select.option>
                            @endforeach
                        </x-ui.select>
                    </x-ui.field>
                @endif

                <x-ui.button wire:click="addBenefit" icon="arrow-down-tray" variant="primary" color="teal">
                    Incluir
                </x-ui.button>
            </div>
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
                            <th class="pb-2 pl-4">
                                <x-ui.text class="font-semibold">Eventos / Usos</x-ui.text>
                            </th>
                            <th class="pb-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($benefits as $benefit)
                            @php
                                $isService = (bool) $benefit->doctor_service_id;
                                $name = $isService 
                                    ? ($benefit->doctorService->service->name ?? 'N/A')
                                    : ($benefit->doctorCoupon->coupon->name ?? 'N/A');
                                $doctor = $isService
                                    ? ($benefit->doctorService->doctor->user->name ?? 'N/A')
                                    : ($benefit->doctorCoupon->doctor->user->name ?? 'N/A');
                            @endphp
                            <tr wire:key="benefit-{{ $benefit->id }}">
                                <td class="align-top">
                                    <div class="flex flex-col">
                                        <x-ui.text class="font-medium">{{ $name }}</x-ui.text>
                                        <x-ui.text size="xs" class="text-slate-500">{{ $doctor }}</x-ui.text>
                                    </div>
                                </td>

                                <td class="align-top">
                                    <x-ui.text>
                                        {{ $isService ? 'Servicio' : 'Cupón' }}
                                    </x-ui.text>
                                </td>

                                <td class="pl-4 align-top">
                                    <x-ui.field>
                                        <x-ui.input wire:model.defer="events.{{ $benefit->id }}" type="number" min="0" placeholder="0"/>
                                    </x-ui.field>
                                </td>

                                <td class="pl-4 align-top">
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

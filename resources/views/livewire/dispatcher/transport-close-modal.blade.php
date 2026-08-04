<x-ui.modal
    id="transport-close-modal"
    animation="fade"
    width="3xl"
    heading="Finalizar traslado"
    description="Resumen del servicio realizado y cierre de cuenta"
    x-on:open-transport-close-modal.window="$data.open()"
    x-on:close-transport-close-modal.window="$data.close()"
>
    @if($closingAppointment)
        <div class="space-y-5">
            <div>
                <x-ui.text class="text-sm font-semibold mb-2">Servicios realizados</x-ui.text>

                <div class="space-y-2">
                    @foreach($closePerformedServices as $service)
                        <div class="flex items-center justify-between rounded-xl border border-neutral-200 bg-white p-3">
                            <div class="flex flex-col gap-1">
                                <x-ui.text class="font-semibold text-base">{{ $service['name'] }}</x-ui.text>
                            </div>

                            <div class="flex items-center gap-3">
                                <x-ui.text class="font-semibold text-neutral-800">${{ number_format((float) $service['amount'], 2) }}</x-ui.text>
                                <x-ui.badge
                                    :icon="$service['covered'] ? 'check-circle' : 'exclamation-triangle'"
                                    variant="outline"
                                    :color="$service['covered'] ? 'green' : 'yellow'"
                                    pill
                                >
                                    {{ $service['covered'] ? 'Incluido' : 'Adicional' }}
                                </x-ui.badge>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            @if(! empty($closeAdditionalServices))
                <div>
                    <x-ui.text class="text-sm font-semibold mb-2">Insumos adicionales</x-ui.text>

                    <div class="space-y-2">
                        @foreach($closeAdditionalServices as $service)
                            <div class="flex items-center justify-between rounded-xl border border-neutral-200 bg-white p-3">
                                <div class="flex flex-col gap-1">
                                    <x-ui.text class="font-semibold text-base">{{ $service['name'] }}</x-ui.text>
                                </div>

                                <div class="flex items-center gap-3">
                                    <x-ui.text class="font-semibold text-neutral-800">${{ number_format((float) $service['amount'], 2) }}</x-ui.text>
                                    <x-ui.badge
                                        :icon="$service['covered'] ? 'check-circle' : 'exclamation-triangle'"
                                        variant="outline"
                                        :color="$service['covered'] ? 'green' : 'yellow'"
                                        pill
                                    >
                                        {{ $service['covered'] ? 'Incluido' : 'Adicional' }}
                                    </x-ui.badge>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <x-ui.card size="full" class="border border-neutral-200 bg-neutral-50">
                <div class="space-y-3 text-sm">
                    <div class="flex items-center justify-between">
                        <x-ui.text>Total de servicios</x-ui.text>
                        <x-ui.text class="font-semibold">${{ number_format($closeSubtotal, 2) }}</x-ui.text>
                    </div>

                    <div class="flex items-center justify-between">
                        <x-ui.text>Descuento a miembro INMAX</x-ui.text>
                        <x-ui.text class="font-semibold text-red-600">-${{ number_format($closeDiscount, 2) }}</x-ui.text>
                    </div>

                    <div class="rounded-xl bg-teal-50 border border-teal-100 px-3 py-2 flex items-center justify-between">
                        <x-ui.text class="font-semibold text-teal-900">Cobro al paciente</x-ui.text>
                        <x-ui.text class="font-bold text-teal-900">${{ number_format($closeUserPayment, 2) }}</x-ui.text>
                    </div>

                    <div class="rounded-xl bg-slate-100 border border-slate-200 px-3 py-2 flex items-center justify-between">
                        <x-ui.text class="font-semibold text-slate-700">Flujo INMAX</x-ui.text>
                        <x-ui.text class="font-bold text-slate-700">${{ number_format($closeCommission, 2) }}</x-ui.text>
                    </div>

                    <div class="rounded-xl bg-slate-900 px-3 py-2 flex items-center justify-between">
                        <x-ui.text class="font-semibold text-white">Ganancia del proveedor</x-ui.text>
                        <x-ui.text class="font-bold text-white">${{ number_format($closeProviderTotal, 2) }}</x-ui.text>
                    </div>
                </div>
            </x-ui.card>

            <x-ui.card size="full" class="border border-neutral-200">
                <x-ui.text class="text-sm font-semibold">FRAP — Registro de atención prehospitalaria</x-ui.text>

                <div class="mt-3">
                    <input
                        type="file"
                        wire:model="frapAttachment"
                        accept=".pdf,.jpg,.jpeg,.png"
                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200"
                    />
                    <x-ui.error name="frapAttachment" />
                    <div wire:loading wire:target="frapAttachment" class="text-sm text-neutral-500 mt-2">
                        Subiendo archivo...
                    </div>
                </div>
            </x-ui.card>

            <div class="flex justify-end gap-3 pt-1">
                <x-ui.button wire:click="resetCloseState" x-on:click="$data.close()" icon="x-mark" variant="outline" color="zinc">
                    Cancelar
                </x-ui.button>

                <x-ui.button wire:click="finalizeCloseTransport" icon="check-circle" color="teal">
                    Finalizar traslado
                </x-ui.button>
            </div>
        </div>
    @endif
</x-ui.modal>

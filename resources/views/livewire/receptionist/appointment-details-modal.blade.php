<x-ui.modal
    id="receptionist-appointment-detail-modal"
    animation="fade"
    width="2xl"
    heading="Detalle de consulta"
    description="Informacion de paciente, servicios y cierre de cuenta"
    x-on:open-receptionist-appointment-detail-modal.window="$data.open()"
    x-on:close-receptionist-appointment-detail-modal.window="$data.close()"
>
    @if($selectedAppointment)
        <div class="space-y-4">
            <div class="grid gap-2 md:grid-cols-2 text-sm">
                <p><span class="font-semibold">Paciente:</span> {{ $selectedAppointment->user?->name ?? 'Sin paciente' }}</p>
                <p><span class="font-semibold">No. Membresia:</span> {{ $selectedAppointment->user?->policy?->number ?? '-' }}</p>
                <p><span class="font-semibold">Proveedor:</span> {{ $selectedAppointment->doctor?->user?->name ?? $selectedAppointment->office?->name ?? 'Sin proveedor' }}</p>
                <p><span class="font-semibold">Especialidad/Tipo:</span> {{ $selectedAppointment->doctor?->specialty?->name ?? $selectedAppointment->doctor?->type?->label() ?? 'Consulta por oficina' }}</p>
                <p><span class="font-semibold">Fecha consulta:</span> {{ $selectedAppointment->note?->created_at->format('d/m/Y') ?? $selectedAppointment->date?->format('d/m/Y') }} {{ $selectedAppointment->note?->created_at->format('h:i A') ?? $selectedAppointment->date?->format('h:i A') }}</p>
                <p><span class="font-semibold">Estatus:</span> {{ $selectedAppointment->formatted_status }}</p>
            </div>

            <div>
                <p class="font-semibold text-sm mb-2">Servicios aplicados</p>

                @php
                    $completedServices = $selectedAppointment->services->filter(fn ($service) => $service->status === 'Completed');
                @endphp

                @if($completedServices->isEmpty())
                    <p class="text-sm text-neutral-500">No hay servicios completados en esta consulta.</p>
                @else
                    <div class="flex flex-col w-full gap-2">
                        @foreach($completedServices as $service)
                            <div class="flex p-2 bg-[#FFFFFF] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                                <x-ui.avatar size="xl" icon="user" color="teal" src="/img/checkup.png" circle />

                                <div class="flex flex-col w-full">
                                    <div class="flex items-center justify-between pl-4 pb-2">
                                        <x-ui.text class="text-base pr-1">{{ $service->service?->name ?? 'Servicio' }}</x-ui.text>
                                        <x-ui.badge :icon="$service->covered_icon" variant="outline" :color="$service->covered_color" pill>
                                            {{ $service->covered_text }}
                                        </x-ui.badge>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            @php
                $allServicesCovered = $selectedAppointment->services->isNotEmpty() && $selectedAppointment->services->every(fn ($s) => (bool) $s->covered);
            @endphp

            <div class="space-y-2 border-t border-neutral-200 pt-3">
                <p class="text-sm">
                    <span class="font-semibold">Fecha pago:</span>
                    @if(is_null($selectedAppointment->user_payment))
                        -
                    @else
                        {{ $selectedAppointment->updated_at?->format('d/m/Y h:i A') ?? '-' }}
                    @endif
                </p>
                <p class="text-sm"><span class="font-semibold">Total cuenta:</span> ${{ $allServicesCovered && is_null($selectedAppointment->user_payment) ? '0.00' : number_format((float) $selectedAppointment->subtotal, 2) }}</p>

                @if((float) $selectedAppointment->coupon_discount > 0)
                    <p class="text-sm"><span class="font-semibold">Descuento cupon:</span> -${{ number_format((float) $selectedAppointment->coupon_discount, 2) }}</p>
                @else
                    <p class="text-sm"><span class="font-semibold">Descuento cupon:</span> No aplicado</p>
                @endif

                <p class="text-sm"><span class="font-semibold">Cobro al paciente:</span> ${{ number_format((float) $selectedAppointment->user_payment, 2) }}</p>
                <p class="text-sm"><span class="font-semibold">Comision Inmax:</span> ${{ number_format((float) $selectedAppointment->commission, 2) }}</p>
                <p class="text-sm"><span class="font-semibold">Ganancia proveedor:</span> ${{ number_format((float) $selectedAppointment->total, 2) }}</p>
            </div>
        </div>
    @endif
</x-ui.modal>

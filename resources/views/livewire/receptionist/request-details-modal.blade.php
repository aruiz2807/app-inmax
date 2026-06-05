<x-ui.modal
    id="receptionist-request-detail-modal"
    animation="fade"
    width="2xl"
    heading="Detalle de solicitud"
    description="Informacion de paciente y agenda propuesta"
    x-on:open-receptionist-request-detail-modal.window="$data.open()"
    x-on:close-receptionist-request-detail-modal.window="$data.close()"
>
    @if($selectedRequest)
        <div class="space-y-4">
            <div class="grid gap-2 md:grid-cols-2 text-sm">
                <p><span class="font-semibold">Paciente:</span> {{ $selectedRequest->user?->name ?? 'Sin paciente' }}</p>
                <p><span class="font-semibold">No. Membresia:</span> {{ $selectedRequest->user?->policy?->number ?? '-' }}</p>
                <p><span class="font-semibold">Doctor:</span> {{ $selectedRequest->doctor?->user?->name ?? 'Sin doctor' }}</p>
                <p><span class="font-semibold">Especialidad:</span> {{ $selectedRequest->doctor?->specialty?->name ?? '-' }}</p>
                <p><span class="font-semibold">Fecha propuesta:</span> {{ $selectedRequest->date?->format('d/m/Y') ?? '-' }} {{ $selectedRequest->time?->format('h:i A') ?? '-' }}</p>
                <p><span class="font-semibold">Estatus:</span> {{ $selectedRequest->formatted_status }}</p>
            </div>

            <div>
                <p class="font-semibold text-sm mb-2">Servicios solicitados</p>

                @if($selectedRequest->services->isEmpty())
                    <p class="text-sm text-neutral-500">No hay servicios vinculados a esta solicitud.</p>
                @else
                    <div class="flex flex-col w-full gap-2">
                        @foreach($selectedRequest->services as $appointmentService)
                            <div class="flex p-2 bg-[#FFFFFF] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                                <x-ui.avatar size="xl" icon="user" color="teal" src="/img/checkup.png" circle />

                                <div class="flex flex-col w-full">
                                    <div class="flex items-center justify-between pl-4 pb-2">
                                        <x-ui.text class="text-base pr-1">{{ $appointmentService->name ?? 'Servicio' }}</x-ui.text>
                                        <x-ui.badge :icon="$appointmentService->covered_icon" variant="outline" :color="$appointmentService->covered_color" pill>
                                            {{ $appointmentService->covered_text }}
                                        </x-ui.badge>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    @endif
</x-ui.modal>

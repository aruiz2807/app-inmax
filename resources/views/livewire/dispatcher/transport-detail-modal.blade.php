<x-ui.modal
    id="transport-detail-modal"
    animation="fade"
    width="3xl"
    heading="Detalle de traslado"
    description="Informacion completa del traslado"
    x-on:open-transport-detail-modal.window="$data.open()"
    x-on:close-transport-detail-modal.window="$data.close()"
>
    @if($selectedAppointment)
        @php
            $statusValue = $selectedAppointment->status?->value;

            $statusMap = [
                'Requested' => ['label' => 'Programado', 'icon' => 'clock', 'color' => 'violet'],
                'Booked' => ['label' => 'En progreso', 'icon' => 'clock', 'color' => 'blue'],
                'Completed' => ['label' => 'Finalizado', 'icon' => 'check-circle', 'color' => 'green'],
                'Rejected' => ['label' => 'Rechazado', 'icon' => 'x-circle', 'color' => 'red'],
                'Cancelled' => ['label' => 'Cancelado', 'icon' => 'x-circle', 'color' => 'red'],
                'No-show' => ['label' => 'No se presento', 'icon' => 'eye-slash', 'color' => 'red'],
            ];

            $statusMeta = $statusMap[$statusValue] ?? ['label' => $selectedAppointment->formatted_status ?: '-', 'icon' => 'information-circle', 'color' => 'slate'];

            $severity = is_array($selectedAppointment->severity_assessment)
                ? $selectedAppointment->severity_assessment
                : (json_decode((string) $selectedAppointment->severity_assessment, true) ?: []);

            $transportService = $selectedAppointment->services->first(fn ($service) => filled($service->service_id));
            $additionalServices = $selectedAppointment->services->filter(fn ($service) => blank($service->service_id));
        @endphp

        <div class="space-y-5">
            <div class="flex justify-end">
                <x-ui.badge :icon="$statusMeta['icon']" variant="outline" :color="$statusMeta['color']" pill>
                    {{ $statusMeta['label'] }}
                </x-ui.badge>
            </div>

            <div class="grid gap-3 md:grid-cols-2">
                <x-ui.card size="full" class="border border-neutral-200">
                    <x-ui.heading class="flex items-center pb-2" level="h3" size="sm">
                        <x-ui.icon name="user" class="self-center" />
                        <x-ui.text class="text-base ml-2">Paciente</x-ui.text>
                    </x-ui.heading>

                    <div class="grid gap-2 text-sm">
                        <p><span class="font-semibold">Nombre:</span> {{ $selectedAppointment->user?->name ?? 'N/A' }}</p>
                        <p><span class="font-semibold">Membresia:</span> {{ $selectedAppointment->user?->policy?->number ?? '-' }}</p>
                        <p><span class="font-semibold">Telefono:</span> {{ $selectedAppointment->user?->cleanPhone ?? 'N/D' }}</p>
                        <p><span class="font-semibold">Edad:</span> {{ $selectedAppointment->user?->age ?? 'N/D' }}</p>
                    </div>
                </x-ui.card>

                <x-ui.card size="full" class="border border-neutral-200">
                    <x-ui.heading class="flex items-center pb-2" level="h3" size="sm">
                        <x-ui.icon name="clipboard-document-list" class="self-center" />
                        <x-ui.text class="text-base ml-2">Datos operativos</x-ui.text>
                    </x-ui.heading>

                    <div class="grid gap-2 text-sm">
                        <p><span class="font-semibold">Proveedor:</span> {{ $selectedAppointment->doctor?->user?->name ?? $selectedAppointment->office?->name ?? 'N/A' }}</p>
                        <p><span class="font-semibold">Estatus:</span> {{ $statusMeta['label'] }}</p>
                        <p><span class="font-semibold">Fecha:</span> {{ $selectedAppointment->date?->format('d/m/Y') ?? '-' }}</p>
                        <p><span class="font-semibold">Hora:</span> {{ $selectedAppointment->time?->format('H:i') ?? '-' }}</p>
                        <p><span class="font-semibold">Origen:</span> {{ $selectedAppointment->origin_address ?? '-' }}</p>
                        <p><span class="font-semibold">Destino:</span> {{ $selectedAppointment->destination_address ?? '-' }}</p>
                        <p><span class="font-semibold">Notas:</span> {{ $selectedAppointment->comments ?: 'Sin notas' }}</p>
                    </div>
                </x-ui.card>
            </div>

            <div>
                <p class="font-semibold text-sm mb-2">Tipo de traslado</p>

                @if($transportService)
                    <div class="flex items-center justify-between rounded-xl border border-neutral-200 bg-white p-3">
                        <div class="flex flex-col">
                            <x-ui.text class="font-semibold text-base">{{ $transportService->service?->name ?? 'Servicio' }}</x-ui.text>
                            <x-ui.text class="text-xs text-neutral-500">Servicio principal del traslado</x-ui.text>
                        </div>

                        <x-ui.badge :icon="$transportService->covered_icon" variant="outline" :color="$transportService->covered_color" pill>
                            {{ $transportService->covered_text }}
                        </x-ui.badge>
                    </div>
                @else
                    <p class="text-sm text-neutral-500">No hay tipo de traslado registrado.</p>
                @endif
            </div>

            <div>
                <p class="font-semibold text-sm mb-2">Insumos y adicionales</p>

                @if($additionalServices->isEmpty())
                    <p class="text-sm text-neutral-500">No hay insumos o adicionales registrados.</p>
                @else
                    <div class="flex flex-col gap-2">
                        @foreach($additionalServices as $service)
                            <div class="flex items-center justify-between rounded-xl border border-neutral-200 bg-white p-3">
                                <div class="flex flex-col">
                                    <x-ui.text class="font-semibold text-base">{{ $service->unregistered_service ?? $service->name ?? 'Servicio adicional' }}</x-ui.text>
                                    <x-ui.text class="text-xs text-neutral-500">Adicional del traslado</x-ui.text>
                                </div>

                                <x-ui.badge :icon="$service->covered_icon" variant="outline" :color="$service->covered_color" pill>
                                    {{ $service->covered_text }}
                                </x-ui.badge>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <div>
                <p class="font-semibold text-sm mb-2">Evaluacion de severidad</p>

                <div class="grid gap-2 md:grid-cols-2 text-sm rounded-xl border border-neutral-200 bg-white p-4">
                    <p><span class="font-semibold">Consciente:</span> {{ array_key_exists('conscious', $severity) ? ($severity['conscious'] ? 'Si' : 'No') : 'N/D' }}</p>
                    <p><span class="font-semibold">Respiracion:</span> {{ $severity['breathing'] ?? 'N/D' }}</p>
                    <p><span class="font-semibold">Hemorragia activa:</span> {{ array_key_exists('bleeding', $severity) ? ($severity['bleeding'] ? 'Si' : 'No') : 'N/D' }}</p>
                    <p><span class="font-semibold">Dolor toracico:</span> {{ array_key_exists('chest_pain', $severity) ? ($severity['chest_pain'] ? 'Si' : 'No') : 'N/D' }}</p>
                    <p class="md:col-span-2"><span class="font-semibold">Escala de dolor:</span> {{ $severity['pain_scale'] ?? 0 }} / 10</p>
                </div>
            </div>

        </div>
    @endif
</x-ui.modal>

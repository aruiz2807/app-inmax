<x-ui.modal
    id="history-appointment-modal"
    animation="fade"
    width="5xl"
    heading="Historial clinico completo"
    description="Consultas, notas medicas, recetas y archivos de resultados del miembro"
    x-on:open-history-appointment-modal.window="$data.open()"
    x-on:close-history-appointment-modal.window="$data.close()"
>
    @if($historyPatient)
        <div class="space-y-4 max-h-[80vh] overflow-y-auto pr-1">
            <x-ui.card size="full" class="border-t-2 border-teal-500">
                <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <x-ui.text class="text-base font-semibold">{{ $historyPatient->name }}</x-ui.text>
                        <x-ui.text class="text-sm opacity-75">No. Membresia: {{ $historyPatient->policy?->number ?? '-' }}</x-ui.text>
                    </div>

                    <div class="flex items-center gap-2">
                        <x-ui.badge icon="calendar" variant="outline" color="teal" pill>
                            {{ $historyAppointments?->count() ?? 0 }} consultas
                        </x-ui.badge>
                    </div>
                </div>
            </x-ui.card>

            @if(blank($historyAppointments) || $historyAppointments->isEmpty())
                <x-ui.card size="full">
                    <x-ui.text class="text-sm opacity-70">No se encontraron consultas para este miembro.</x-ui.text>
                </x-ui.card>
            @else
                <div class="space-y-6">
                    @foreach($historyAppointments as $historyItem)
                        @php
                            $completedServices = $historyItem->services
                                ->where('status', \App\Enums\AppointmentStatus::COMPLETED->value)
                                ->values();

                        @endphp

                        <div class="rounded-2xl border-2 border-teal-200 bg-white p-1.5 shadow-sm" x-data="{ open: @js($loop->first) }">
                            <x-ui.card size="full" class="border border-neutral-100">
                                <div class="flex flex-col gap-3">
                                <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between pb-2 border-b border-neutral-100">
                                    <div class="space-y-1">
                                        <x-ui.text class="text-base font-semibold">
                                            Consulta #{{ $historyItem->id }} - {{ $historyItem->doctor?->user?->name ?? $historyItem->office?->name ?? 'Sin proveedor' }}
                                        </x-ui.text>
                                        <x-ui.text class="text-sm opacity-80">
                                            {{ $historyItem->date?->format('d/m/Y') }} {{ $historyItem->time?->format('h:i A') }}
                                        </x-ui.text>
                                    </div>

                                    <div class="flex items-center gap-2">
                                        <x-status-badge :status="$historyItem->status?->value ?? ''" />

                                        <button
                                            type="button"
                                            x-on:click="open = !open"
                                            class="inline-flex items-center gap-1 rounded-md border border-neutral-300 px-2 py-1 text-xs font-medium text-neutral-700 hover:bg-neutral-50"
                                        >
                                            <span x-show="!open">Expandir</span>
                                            <span x-show="open">Contraer</span>
                                            <x-ui.icon name="chevron-down" class="w-4 h-4 transition-transform" x-bind:class="open ? 'rotate-180' : ''" />
                                        </button>
                                    </div>
                                </div>

                                <div x-show="open" x-transition.opacity.duration.200ms class="space-y-4">
                                    <div class="grid gap-2 text-sm md:grid-cols-2">
                                        <p><span class="font-semibold">Proveedor:</span> {{ $historyItem->doctor?->user?->name ?? $historyItem->office?->name ?? 'Sin proveedor' }}</p>
                                        <p><span class="font-semibold">Especialidad/Tipo:</span> {{ $historyItem->doctor?->specialty?->name ?? 'Consulta por oficina' }}</p>
                                    </div>

                                    <div class="grid grid-cols-1 gap-4 xl:grid-cols-2">
                                        <x-ui.card size="full" class="bg-neutral-50 border border-neutral-200">
                                            <x-ui.heading class="flex pb-2" level="h3" size="sm">
                                                <x-ui.icon name="clipboard-document-list" class="self-center" />
                                                <x-ui.text class="ml-2 text-base">Consultas realizadas / Servicios</x-ui.text>
                                            </x-ui.heading>

                                            @if($completedServices->isEmpty())
                                                <x-ui.text class="text-sm opacity-70">Sin servicios completados en esta cita.</x-ui.text>
                                            @else
                                                <div class="grid grid-cols-1 gap-2">
                                                    @foreach($completedServices as $service)
                                                        <div class="flex items-center justify-between rounded-lg border border-neutral-200 bg-white p-3">
                                                            <x-ui.text class="pr-2 text-sm">{{ $service->name ?? 'Servicio' }}</x-ui.text>
                                                            <x-ui.badge :icon="$service->covered_icon" variant="outline" :color="$service->covered_color" pill>
                                                                {{ $service->covered_text }}
                                                            </x-ui.badge>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </x-ui.card>

                                        <x-ui.card size="full" class="bg-neutral-50 border border-neutral-200">
                                            <x-ui.heading class="flex pb-2" level="h3" size="sm">
                                                <x-ui.icon name="document-text" class="self-center" />
                                                <x-ui.text class="ml-2 text-base">Recetas emitidas</x-ui.text>
                                            </x-ui.heading>

                                            @if($historyItem->prescriptions->isEmpty())
                                                <x-ui.text class="text-sm opacity-70">No hay recetas para esta cita.</x-ui.text>
                                            @else
                                                <div class="grid grid-cols-1 gap-2">
                                                    @foreach($historyItem->prescriptions as $prescription)
                                                        <div class="rounded-lg border border-gray-100 bg-white p-2 shadow-sm">
                                                            @if($prescription->medication)
                                                                <x-ui.text class="text-sm font-bold">{{ $prescription->medication->name }} ({{ $prescription->medication->trade_name ?? '' }})</x-ui.text>
                                                                <x-ui.text class="text-xs text-gray-600">
                                                                    {{ $prescription->quantity }} {{ $prescription->medication->packaging }} - {{ $prescription->dose }} - {{ $prescription->frequency }} - {{ $prescription->duration }}
                                                                </x-ui.text>
                                                            @else
                                                                <x-ui.text class="text-sm font-bold">{{ $prescription->description }}</x-ui.text>
                                                                <x-ui.text class="text-xs text-gray-600">
                                                                    {{ $prescription->quantity }} {{ $prescription->dose }} - {{ $prescription->frequency }} - {{ $prescription->duration }}
                                                                </x-ui.text>
                                                            @endif
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </x-ui.card>
                                    </div>

                                    <x-ui.card size="full" class="bg-neutral-50 border border-neutral-200">
                                        <x-ui.heading class="flex pb-2" level="h3" size="sm">
                                            <x-ui.icon name="clipboard-document-list" class="self-center" />
                                            <x-ui.text class="ml-2 text-base">Nota medica</x-ui.text>
                                        </x-ui.heading>

                                        @if($historyItem->note)
                                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                                <div>
                                                    <x-ui.text class="text-xs font-semibold uppercase opacity-70">Sintomas</x-ui.text>
                                                    <x-ui.text class="text-sm">{{ $historyItem->note->symptoms ?: 'Sin captura' }}</x-ui.text>
                                                </div>
                                                <div>
                                                    <x-ui.text class="text-xs font-semibold uppercase opacity-70">Hallazgos fisicos</x-ui.text>
                                                    <x-ui.text class="text-sm">{{ $historyItem->note->findings ?: 'Sin captura' }}</x-ui.text>
                                                </div>
                                                <div>
                                                    <x-ui.text class="text-xs font-semibold uppercase opacity-70">Diagnostico</x-ui.text>
                                                    <x-ui.text class="text-sm">{{ $historyItem->note->diagnosis ?: 'Sin captura' }}</x-ui.text>
                                                </div>
                                                <div>
                                                    <x-ui.text class="text-xs font-semibold uppercase opacity-70">Tratamiento</x-ui.text>
                                                    <x-ui.text class="text-sm">{{ $historyItem->note->treatment ?: 'Sin captura' }}</x-ui.text>
                                                </div>
                                                <div class="md:col-span-2">
                                                    <x-ui.text class="text-xs font-semibold uppercase opacity-70">Notas y recomendaciones</x-ui.text>
                                                    <x-ui.text class="text-sm">{{ $historyItem->note->notes ?: 'Sin captura' }}</x-ui.text>
                                                </div>
                                                <div class="md:col-span-2">
                                                    <x-ui.text class="text-xs font-semibold uppercase opacity-70">Enlace/comentario de resultados</x-ui.text>
                                                    @if(filled($historyItem->note->results_comment))
                                                        <a href="{{ $historyItem->note->results_comment }}" target="_blank" class="text-blue-600 hover:underline break-all text-sm">
                                                            {{ $historyItem->note->results_comment }}
                                                        </a>
                                                    @else
                                                        <x-ui.text class="text-sm">Sin captura</x-ui.text>
                                                    @endif
                                                </div>
                                            </div>
                                        @else
                                            <x-ui.text class="text-sm opacity-70">Esta cita no tiene nota medica registrada.</x-ui.text>
                                        @endif
                                    </x-ui.card>

                                    <x-ui.card size="full" class="bg-neutral-50 border border-neutral-200">
                                        <x-ui.heading class="flex pb-2" level="h3" size="sm">
                                            <x-ui.icon name="paper-clip" class="self-center" />
                                            <x-ui.text class="ml-2 text-base">Archivos y analisis cargados</x-ui.text>
                                        </x-ui.heading>

                                        @if($completedServices->isEmpty())
                                            <x-ui.text class="text-sm opacity-70">Esta cita no tiene servicios completados para adjuntar resultados.</x-ui.text>
                                        @else
                                            <div class="space-y-4">
                                                @foreach($completedServices as $service)
                                                    @php
                                                        $extension = strtolower(pathinfo((string) $service->attachment_name, PATHINFO_EXTENSION));
                                                        $isImage = in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true);
                                                        $isPdf = $extension === 'pdf';
                                                    @endphp

                                                    <div class="rounded-lg border border-neutral-200 bg-white p-3">
                                                        <div class="grid grid-cols-1 gap-2 md:grid-cols-5 md:items-center">
                                                            <div class="md:col-span-2">
                                                                <x-ui.text class="text-sm font-semibold">{{ $service->name ?? 'Servicio' }}</x-ui.text>

                                                                @if($service->attachment_name)
                                                                    <x-ui.text class="mt-1 text-xs text-neutral-500">Adjunto actual:</x-ui.text>
                                                                    <a href="{{ route('attachment.download', $service->id) }}" class="text-sm text-sky-700 hover:underline break-all">
                                                                        {{ $service->attachment_name }}
                                                                    </a>
                                                                @endif
                                                            </div>

                                                            <div class="md:col-span-3 space-y-2">
                                                                <input
                                                                    type="file"
                                                                    wire:model="historyServiceAttachments.{{ $historyItem->id }}.{{ $service->id }}"
                                                                    placeholder="Seleccione un archivo para adjuntar"
                                                                    class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                                                />

                                                                <x-ui.button
                                                                    type="button"
                                                                    color="teal"
                                                                    icon="paper-clip"
                                                                    wire:click="saveHistoryServiceAttachment({{ $historyItem->id }}, {{ $service->id }})"
                                                                    class="w-full md:w-auto"
                                                                >
                                                                    Guardar archivo
                                                                </x-ui.button>

                                                                <x-ui.error name="historyServiceAttachments.{{ $historyItem->id }}.{{ $service->id }}" />

                                                                <div wire:loading wire:target="historyServiceAttachments.{{ $historyItem->id }}.{{ $service->id }}" class="text-sm text-neutral-600">
                                                                    Subiendo archivo...
                                                                </div>
                                                            </div>
                                                        </div>

                                                        @if($service->attachment_name)
                                                            @if($isImage)
                                                                <img
                                                                    src="{{ route('attachment.preview', $service->id) }}"
                                                                    alt="Archivo de resultados"
                                                                    class="mt-3 w-full rounded-lg border border-neutral-200 object-contain max-h-80"
                                                                />
                                                            @elseif($isPdf)
                                                                <iframe
                                                                    src="{{ route('attachment.preview', $service->id) }}"
                                                                    class="mt-3 h-80 w-full rounded-lg border border-neutral-200"
                                                                    title="Vista previa PDF"
                                                                ></iframe>
                                                            @else
                                                                <x-ui.text class="mt-3 text-xs text-neutral-600">
                                                                    Vista previa no disponible para este formato. Puede descargar el archivo para revisarlo.
                                                                </x-ui.text>
                                                            @endif
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>

                                            <div class="pt-2">
                                                <x-ui.field>
                                                    <x-ui.label>Enlace de resultados (opcional)</x-ui.label>
                                                    <x-ui.input
                                                        wire:model.defer="historyResultsComments.{{ $historyItem->id }}"
                                                        placeholder="https://..."
                                                    />
                                                </x-ui.field>
                                                <x-ui.error name="historyResultsComments.{{ $historyItem->id }}" />
                                                <x-ui.text class="mt-1 text-xs text-neutral-500">
                                                    Si no adjuntas archivo, puedes guardar un enlace con los resultados.
                                                </x-ui.text>

                                                <x-ui.button
                                                    type="button"
                                                    color="teal"
                                                    icon="link"
                                                    wire:click="saveHistoryResultsComment({{ $historyItem->id }})"
                                                    class="mt-2"
                                                >
                                                    Guardar enlace
                                                </x-ui.button>
                                            </div>
                                        @endif
                                    </x-ui.card>
                                </div>
                                </div>
                            </x-ui.card>
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="flex justify-end pt-2">
                <x-ui.button x-on:click="$data.close()" icon="x-mark" variant="outline">
                    Cerrar
                </x-ui.button>
            </div>
        </div>
    @endif
</x-ui.modal>

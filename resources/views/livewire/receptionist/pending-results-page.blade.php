<div
    x-data
    x-on:open-receptionist-pending-results-detail.window="$wire.openDetails($event.detail.appointmentId)"
>
    <x-slot name="header">
        Consultas faltantes de resultados
    </x-slot>

    <x-ui.card size="full">
        <x-ui.heading class="flex items-center justify-between mb-4" level="h3" size="sm">
            <span>Consultas faltantes de resultados</span>
        </x-ui.heading>

        <p>Consulta las citas atendidas que todavia tienen servicios sin archivo de resultado.</p>
    </x-ui.card>

    <div class="grid grid-cols-1 gap-4 pt-2 md:grid-cols-1">
        <x-ui.card size="full" class="border-t-2 border-amber-500">
            <p class="text-xs font-semibold tracking-wide uppercase text-neutral-500">Consultas pendientes de resultados</p>
            <p class="mt-2 text-3xl font-bold text-neutral-900">{{ $this->pendingResultsCount }}</p>
            <p class="text-xs text-neutral-500">Atendidas con resultados faltantes</p>
        </x-ui.card>
    </div>

    <div id="pending-results-section" class="pt-2">
        <x-ui.card size="full">
            <livewire:receptionist.pending-results-table :key="'receptionist-pending-results-table-'.$tableIteration" />
        </x-ui.card>
    </div>

    @include('livewire.receptionist.appointment-details-modal')

    <x-ui.modal
        id="receptionist-upload-results-modal"
        animation="fade"
        width="xl"
        heading="Adjuntar resultados"
        description=""
        x-on:open-receptionist-upload-results-modal.window="$data.open()"
        x-on:close-receptionist-upload-results-modal.window="$data.close()"
    >
        <form wire:submit.prevent="saveAndKeepPending" class="flex flex-col gap-3">
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-3">
                <x-ui.text class="text-sm text-amber-900">
                    Detectamos estudios pendientes de archivo. Si ya vienen incluidos en el documento que subiste, puedes finalizar el proceso.
                </x-ui.text>
            </div>

            <x-ui.error name="serviceAttachments" />

            <div class="flex flex-col w-full gap-3">
                @foreach($selectedAppointmentServices as $service)
                    <div class="grid grid-cols-5 items-center gap-2">
                        <div class="col-span-3">
                            <x-ui.text class="text-sm pr-2">{{$service->service->name}}</x-ui.text>
                            @if($service->attachment_name)
                                <x-ui.text class="text-xs text-neutral-500 mt-1">Adjunto actual:</x-ui.text>
                                <a href="{{ route('attachment.download', $service->id) }}" class="text-xs text-blue-600 hover:underline break-all">
                                    {{$service->attachment_name}}
                                </a>
                            @endif
                        </div>

                        <div class="col-span-2">
                            @if($service->attachment_name)
                                <x-ui.badge icon="check-circle" variant="outline" color="green" pill>
                                    Archivo cargado
                                </x-ui.badge>
                            @else
                                <input type="file"
                                       wire:model="serviceAttachments.{{ $service->id }}"
                                       placeholder="Seleccione un archivo para adjuntar"
                                       class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                            @endif
                        </div>
                    </div>

                    <x-ui.error name="serviceAttachments.{{ $service->id }}" />

                    <div wire:loading wire:target="serviceAttachments.{{ $service->id }}">
                        Subiendo archivo...
                    </div>
                @endforeach
            </div>

            @if(empty($selectedAppointmentServices))
                <div class="text-sm text-neutral-600">
                    Esta cita no tiene servicios completados para adjuntar resultados.
                </div>
            @endif

            <div class="pt-1">
                <x-ui.field>
                    <x-ui.label>Enlace de resultados (opcional)</x-ui.label>
                    <x-ui.input
                        wire:model.live="resultsComment"
                        placeholder="https://..."
                    />
                </x-ui.field>
                <x-ui.error name="resultsComment" />
                <x-ui.text class="text-xs text-neutral-500 mt-1">
                    Si no adjuntas archivo, puedes guardar un enlace con los resultados.
                </x-ui.text>
            </div>

            <div class="flex flex-col md:flex-row md:justify-end gap-2 md:gap-3 mt-2">
                <x-ui.button type="button" class="w-full md:w-auto" color="amber" icon="clock" wire:click="saveAndKeepPending">
                    Subir el resto despues
                </x-ui.button>

                <x-ui.button type="button" class="w-full md:w-auto" color="teal" icon="check" wire:click="saveAndFinalize">
                    Ya incluidos, finalizar
                </x-ui.button>
            </div>

            <div class="flex flex-col md:flex-row md:justify-end gap-2 md:gap-3 pt-2">
                <x-ui.button type="button" class="w-full md:w-auto" x-on:click="$data.close()" icon="x-mark" variant="outline">
                    Cancelar
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>

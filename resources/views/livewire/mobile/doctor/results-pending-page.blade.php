<div>
    <div class="relative w-full">
        <img src="/img/top.png" alt="Header" class="w-full object-cover">
    </div>

    <div class="grid grid-cols-[2rem_auto] justify-stretch items-center pt-4 pb-4">
        <x-ui.icon name="arrow-left" class="w-5 h-5 cursor-pointer" x-on:click="window.history.back()" />
        <x-ui.text class="text-2xl">Consultas pendientes de resultados</x-ui.text>
    </div>

    <div class="px-4 pb-8">
        @if($appointments->isEmpty())
            <div class="flex justify-center p-4 bg-[#FFFFFF] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                <x-ui.text class="text-base">No hay consultas pendientes de resultados</x-ui.text>
            </div>
        @endif

        @foreach($appointments as $appointment)
            <div class="flex flex-col p-2 mb-4 bg-[#FFFFFF] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                <div class="flex justify-center mb-4 gap-x-2">
                    <x-ui.badge :icon="$appointment->status_icon" variant="outline" :color="$appointment->status_color" pill>{{$appointment->formatted_status}}</x-ui.badge>
                </div>

                <div class="flex mt-2 mx-auto w-fit">
                    <div class="bg-[#FFFFFF] rounded-xl text-white mr-4">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#00D5BE" viewBox="0 0 256 256">
                            <path d="M208,32H184V24a8,8,0,0,0-16,0v8H88V24a8,8,0,0,0-16,0v8H48A16,16,0,0,0,32,48V208a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V48A16,16,0,0,0,208,32ZM72,48v8a8,8,0,0,0,16,0V48h80v8a8,8,0,0,0,16,0V48h24V80H48V48ZM208,208H48V96H208V208Zm-68-76a12,12,0,1,1-12-12A12,12,0,0,1,140,132Zm44,0a12,12,0,1,1-12-12A12,12,0,0,1,184,132ZM96,172a12,12,0,1,1-12-12A12,12,0,0,1,96,172Zm44,0a12,12,0,1,1-12-12A12,12,0,0,1,140,172Zm44,0a12,12,0,1,1-12-12A12,12,0,0,1,184,172Z"></path>
                        </svg>
                    </div>
                    <div>
                        <x-ui.text class="text-lg">{{$appointment->formatted_date}}</x-ui.text>
                        <x-ui.text class="text-sm opacity-50">{{$appointment->formatted_time}} - {{$appointment->office?->name}}</x-ui.text>
                    </div>
                </div>

                <div class="flex mt-8">
                    <x-ui.avatar size="xl" icon="user" color="teal" :src="$appointment->user->photo_url" circle />
                    <div class="pl-4">
                        <x-ui.text class="pt-1 text-xl">{{$appointment->user->name}}</x-ui.text>
                        <x-ui.text class="text-base opacity-75">{{$appointment->user->policy->number}}</x-ui.text>
                    </div>
                </div>

                <div class="flex mt-4">
                    <x-ui.avatar size="xl" icon="user" color="teal" src="/img/checkup.png" circle />

                    <div class="flex flex-col w-full">
                        @foreach($appointment->services as $service)
                            <div class="flex items-center justify-between pl-4 pb-2">
                                <x-ui.text class="text-base pr-1">{{$service->service->name}}</x-ui.text>
                                <x-ui.badge :icon="$service->covered_icon" variant="outline" :color="$service->covered_color" pill>{{$service->covered_text}}</x-ui.badge>
                            </div>
                        @endforeach
                    </div>
                </div>

                <x-ui.separator class="mt-2 mb-2"/>

                <div class="flex justify-center">
                    <x-ui.button class="w-44" wire:click="openUploadModal({{ $appointment->id }})" variant="outline" color="blue" icon="paper-clip">
                        Cargar archivos
                    </x-ui.button>
                </div>
            </div>
        @endforeach
    </div>

    <x-ui.modal
        id="upload-results-modal"
        animation="fade"
        width="xl"
        heading="Adjuntar resultados"
        x-on:open-upload-results-modal.window="$data.open()"
        x-on:close-upload-results-modal.window="$data.close()"
    >
        <form wire:submit.prevent="saveResultFile" class="flex flex-col gap-3">
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

            <div class="flex flex-col sm:flex-row gap-2 sm:gap-3 mt-2">
                <x-ui.button type="button" wire:click="closeUploadModal" variant="outline" color="zinc" class="w-full sm:w-auto sm:flex-1 rounded-xl text-sm font-medium">
                    Cancelar
                </x-ui.button>
                <x-ui.button type="submit" variant="outline" color="blue" class="w-full sm:w-auto sm:flex-1 rounded-xl text-sm font-medium">
                    Guardar
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>
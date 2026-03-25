<div>
    <div class="relative w-full">
        <img src="/img/top.png" alt="Header" class="w-full object-cover">
    </div>

    <div class="grid grid-cols-[2rem_auto] justify-stretch items-center pt-4 pb-4">
        <x-ui.icon name="arrow-left" class="w-5 h-5 cursor-pointer" x-on:click="window.history.back()" />
        <x-ui.text class="text-2xl">Historial medico</x-ui.text>
    </div>

    <div class="relative w-full">

        <x-ui.alerts variant="info" icon="information-circle">
            <x-ui.alerts.description>
                Información actualizada: <strong> {{now()->format('d/m/Y')}} </strong>
            </x-ui.alerts.description>
        </x-ui.alerts>

        <x-ui.card size="full" class="mt-4">
            <x-ui.accordion>
                <x-ui.accordion.item expanded trigger="Consultas / Estudios">
                    <div class="flex flex-col justify-center p-3 bg-[#FFFFFF] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                        @if($appointments->isEmpty())
                        <x-ui.text class="text-base">No hay consultas / estudios</x-ui.text>
                        @endif

                        @foreach($appointments as $record)
                            <div class="w-full grid grid-cols-[2rem_auto] justify-stretch items-center mt-1 mb-1">
                                <x-ui.icon name="calendar" />
                                <div class="flex flex-col justify-start ml-1" >
                                    <x-ui.text class="text-sm font-semibold">{{$record->date->format('d/m/Y')}}</x-ui.text>

                                    @foreach($record->services as $service)
                                        @if($service->status === 'Completed')
                                        <x-ui.text class="text-sm">{{$service->service->name}}</x-ui.text>
                                        @endif
                                    @endforeach
                                </div>
                            </div>

                            @unless ($loop->last)
                                <x-ui.separator />
                            @endunless
                        @endforeach
                    </div>
                </x-ui.accordion.item>

                <x-ui.accordion.item trigger="Diagnosticos y tratamientos">
                    <div class="flex flex-col justify-center p-3 bg-[#FFFFFF] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                        @if($doctorAppointments->isEmpty())
                        <x-ui.text class="text-base">No hay diagnosticos</x-ui.text>
                        @endif

                        @foreach($doctorAppointments as $record)
                            <div class="w-full grid grid-cols-[2rem_auto] justify-stretch items-center mt-1 mb-1">
                                <x-ui.icon name="clipboard-document-list" />
                                <div class="flex flex-col justify-start ml-1" >
                                    <x-ui.text class="text-sm font-semibold">{{$record->date->format('d/m/Y')}}</x-ui.text>
                                    <x-ui.text class="text-sm"><b>Diagnostico :</b> {{$record->note->diagnosis}}</x-ui.text>
                                    <x-ui.text class="text-sm"><b>Tratamiento :</b> {{$record->note->treatment}}</x-ui.text>
                                </div>
                            </div>

                            @unless ($loop->last)
                                <x-ui.separator />
                            @endunless
                        @endforeach
                    </div>
                </x-ui.accordion.item>

                <x-ui.accordion.item trigger="Imagenología y estudios">
                    <div class="flex flex-col justify-center p-3 bg-[#FFFFFF] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                        @if($exams->isEmpty())
                        <x-ui.text class="text-base">No hay estudios</x-ui.text>
                        @endif

                        @foreach($exams as $record)
                            <div class="w-full grid grid-cols-[2rem_auto] justify-stretch items-center mt-1 mb-1">
                                <x-ui.icon name="paper-clip" />
                                <div class="flex flex-col justify-start ml-1" >
                                    <x-ui.text class="text-sm font-semibold">{{$record->appointment->date->format('d/m/Y')}}</x-ui.text>
                                    <a href="{{ route('attachment.download', $record->id) }}">
                                        <x-ui.text class="text-sm">{{$record->attachment_name}}</x-ui.text>
                                    </a>
                                </div>
                            </div>

                            @unless ($loop->last)
                                <x-ui.separator />
                            @endunless
                        @endforeach
                    </div>
                </x-ui.accordion.item>

                <x-ui.accordion.item trigger="Vacunas">
                    <div class="flex justify-start p-4 bg-[#FFFFFF] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                        <x-ui.text class="text-base">No hay vacunas</x-ui.text>
                    </div>
                </x-ui.accordion.item>
            </x-ui.accordion>
        </x-ui.card>
    </div>
</div>

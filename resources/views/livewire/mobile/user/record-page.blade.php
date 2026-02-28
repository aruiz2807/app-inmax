<div class="max-w-md mx-auto bg-white min-h-screen overflow-hidden font-sans">
    <div class="relative w-full">
        <img src="/img/top.png" alt="Header" class="w-full object-cover">
    </div>

    <div class="grid grid-cols-[2rem_auto] justify-stretch items-center pt-4 pb-4">
        <x-ui.icon name="arrow-left" class="w-5 h-5 cursor-pointer" x-on:click="window.history.back()" />
        <x-ui.text class="text-2xl">Expediente medico</x-ui.text>
    </div>

    <div class="relative w-full">

        <x-ui.alerts variant="info" icon="information-circle">
            <x-ui.alerts.description>
                Información actualizada: <strong> {{now()->format('d/m/Y')}} </strong>
            </x-ui.alerts.description>
        </x-ui.alerts>

        <x-ui.card size="full" class="mt-4">
            <x-ui.accordion>
                <x-ui.accordion.item expanded trigger="Consultas">
                    <div class="flex flex-col justify-center p-3 bg-[#FFFFFF] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                        @if($appointments->isEmpty())
                        <x-ui.text class="text-base">No hay consultas</x-ui.text>
                        @endif
                        @foreach($appointments as $record)
                        <div class="w-full grid grid-cols-[2rem_auto] justify-stretch items-center mt-1 mb-1">
                            <x-ui.icon name="calendar" />
                            <div class="flex flex-col justify-start ml-1" >
                                <x-ui.text class="text-sm font-semibold">{{$record->date->format('d/m/Y')}}</x-ui.text>
                                <x-ui.text class="text-sm">{{$record->note->symptoms}}</x-ui.text>
                            </div>
                        </div>
                        <x-ui.separator />
                        @endforeach
                    </div>
                </x-ui.accordion.item>

                <x-ui.accordion.item trigger="Diagnosticos y tratamientos">
                    <div class="flex flex-col justify-center p-3 bg-[#FFFFFF] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                        @if($appointments->isEmpty())
                        <x-ui.text class="text-base">No hay diagnosticos</x-ui.text>
                        @endif
                        @foreach($appointments as $record)
                        <div class="w-full grid grid-cols-[2rem_auto] justify-stretch items-center mt-1 mb-1">
                            <x-ui.icon name="clipboard-document-list" />
                            <div class="flex flex-col justify-start ml-1" >
                                <x-ui.text class="text-sm font-semibold">{{$record->date->format('d/m/Y')}}</x-ui.text>
                                <x-ui.text class="text-sm"><b>Diagnostico :</b> {{$record->note->diagnosis}}</x-ui.text>
                                <x-ui.text class="text-sm"><b>Tratamiento :</b> {{$record->note->treatment}}</x-ui.text>
                            </div>
                        </div>
                        <x-ui.separator />
                        @endforeach
                    </div>
                </x-ui.accordion.item>

                <x-ui.accordion.item trigger="Imagenologia y examenes">
                    <div class="flex flex-col justify-center p-3 bg-[#FFFFFF] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                        @if($exams->isEmpty())
                        <x-ui.text class="text-base">No hay diagnosticos</x-ui.text>
                        @endif
                        @foreach($exams as $record)
                        <div class="w-full grid grid-cols-[2rem_auto] justify-stretch items-center mt-1 mb-1">
                            <x-ui.icon name="paper-clip" />
                            <div class="flex flex-col justify-start ml-1" >
                                <x-ui.text class="text-sm font-semibold">{{$record->date->format('d/m/Y')}}</x-ui.text>
                                <a href="{{ route('attachment.download', $record->note->id) }}">
                                    <x-ui.text class="text-sm">{{$record->note->attachment_name}}</x-ui.text>
                                </a>
                            </div>
                        </div>
                        <x-ui.separator />
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

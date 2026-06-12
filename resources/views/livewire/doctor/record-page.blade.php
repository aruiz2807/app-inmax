<div class="space-y-4">
    <x-slot name="header">
        Historial medico
    </x-slot>

    <div class="relative w-full">
        <x-ui.alerts variant="info" icon="information-circle">
            <x-ui.alerts.description>
                Información actualizada: <strong> {{now()->format('d/m/Y')}} </strong>
            </x-ui.alerts.description>
        </x-ui.alerts>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-3 mt-4">

            {{-- Columna 1: Diagnosticos y Vacunas --}}
            <div class="space-y-4">

                <x-ui.card size="full" x-data="{ open: true }">
                    <x-ui.heading class="flex pb-2 cursor-pointer select-none" level="h3" size="sm" x-on:click="open = !open">
                        <x-ui.icon name="clipboard-document-list" class="self-center" />
                        <x-ui.text class="ml-2 text-base flex-1">Diagnosticos y tratamientos</x-ui.text>
                        <x-ui.icon name="chevron-up" class="self-center w-4 h-4 transition-transform" x-bind:class="open ? '' : 'rotate-180'" />
                    </x-ui.heading>

                    <div x-show="open" x-collapse>
                        <button wire:click="openUploadForm('{{ \App\Enums\ExternalServicesType::Diagnosis->value }}')"
                                class="flex items-center gap-1 text-sm text-blue-600 font-medium hover:underline mb-3">
                            <x-ui.icon name="arrow-up-tray" class="w-4 h-4" />
                            Subir archivo
                        </button>

                        <div class="flex flex-col justify-center p-3 bg-white rounded-2xl shadow-sm border border-white/50">
                            @if($doctorAppointments->isEmpty() && $externalServices->where('type', \App\Enums\ExternalServicesType::Diagnosis)->isEmpty())
                                <x-ui.text class="text-base">No hay diagnosticos</x-ui.text>
                            @endif

                            @foreach($doctorAppointments as $record)
                                <div class="w-full grid grid-cols-[2rem_auto] justify-stretch items-center mt-1 mb-1">
                                    <x-ui.icon name="clipboard-document-list" />
                                    <div class="flex flex-col justify-start ml-1">
                                        <x-ui.text class="text-sm font-semibold">{{$record->date->format('d/m/Y')}}</x-ui.text>
                                        <x-ui.text class="text-sm"><b>Diagnostico:</b> {{$record->note->diagnosis}}</x-ui.text>
                                        @if(count($record->prescriptions) > 0)
                                            <x-ui.text class="text-sm"><b>Tratamiento / Receta:</b></x-ui.text>
                                            <ul class="list-disc pl-5">
                                                @foreach($record->prescriptions as $prescription)
                                                    <li>
                                                        @if($prescription->medication)
                                                            <x-ui.text class="text-sm">{{ $prescription->medication->name }} ({{ $prescription->medication->trade_name }})</x-ui.text>
                                                        @else
                                                            <x-ui.text class="text-sm">{{ $prescription->description }}</x-ui.text>
                                                        @endif
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                        @if($record->note->treatment)
                                            <x-ui.text class="text-sm"><b>Tratamiento (Notas):</b> {{$record->note->treatment}}</x-ui.text>
                                        @endif
                                    </div>
                                </div>
                                @unless ($loop->last)
                                    <x-ui.separator />
                                @endunless
                            @endforeach

                            @foreach($externalServices->where('type', \App\Enums\ExternalServicesType::Diagnosis) as $ext)
                                @if($doctorAppointments->isNotEmpty() || !$loop->first)
                                    <x-ui.separator />
                                @endif
                                <div class="w-full grid grid-cols-[2rem_auto] justify-stretch items-center mt-1 mb-1">
                                    <x-ui.icon name="paper-clip" />
                                    <div class="flex flex-col justify-start ml-1">
                                        <x-ui.text class="text-sm font-semibold">{{$ext->date->format('d/m/Y')}}</x-ui.text>
                                        @if($ext->attachment_path)
                                            <a href="{{ route('external-service.download', $ext->id) }}">
                                                <x-ui.text class="text-sm">{{$ext->name}}</x-ui.text>
                                            </a>
                                        @else
                                            <x-ui.text class="text-sm">{{$ext->name}}</x-ui.text>
                                        @endif
                                        @if($ext->comments)
                                            <x-ui.text class="text-xs text-gray-500">{{$ext->comments}}</x-ui.text>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </x-ui.card>

                <x-ui.card size="full" x-data="{ open: false }">
                    <x-ui.heading class="flex pb-2 cursor-pointer select-none" level="h3" size="sm" x-on:click="open = !open">
                        <x-ui.icon name="paper-clip" class="self-center" />
                        <x-ui.text class="ml-2 text-base flex-1">Vacunas</x-ui.text>
                        <x-ui.icon name="chevron-up" class="self-center w-4 h-4 transition-transform" x-bind:class="open ? '' : 'rotate-180'" />
                    </x-ui.heading>

                    <div x-show="open" x-collapse>
                        <button wire:click="openUploadForm('{{ \App\Enums\ExternalServicesType::Vaccine->value }}')"
                                class="flex items-center gap-1 text-sm text-blue-600 font-medium hover:underline mb-3">
                            <x-ui.icon name="arrow-up-tray" class="w-4 h-4" />
                            Subir archivo
                        </button>

                        <div class="flex flex-col justify-center p-3 bg-white rounded-2xl shadow-sm border border-white/50">
                            @if($externalServices->where('type', \App\Enums\ExternalServicesType::Vaccine)->isEmpty())
                                <x-ui.text class="text-base">No hay vacunas</x-ui.text>
                            @endif

                            @foreach($externalServices->where('type', \App\Enums\ExternalServicesType::Vaccine) as $ext)
                                @unless($loop->first)
                                    <x-ui.separator />
                                @endunless
                                <div class="w-full grid grid-cols-[2rem_auto] justify-stretch items-center mt-1 mb-1">
                                    <x-ui.icon name="paper-clip" />
                                    <div class="flex flex-col justify-start ml-1">
                                        <x-ui.text class="text-sm font-semibold">{{$ext->date->format('d/m/Y')}}</x-ui.text>
                                        @if($ext->attachment_path)
                                            <a href="{{ route('external-service.download', $ext->id) }}">
                                                <x-ui.text class="text-sm">{{$ext->name}}</x-ui.text>
                                            </a>
                                        @else
                                            <x-ui.text class="text-sm">{{$ext->name}}</x-ui.text>
                                        @endif
                                        @if($ext->comments)
                                            <x-ui.text class="text-xs text-gray-500">{{$ext->comments}}</x-ui.text>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </x-ui.card>

            </div>

            {{-- Columna 2: Consultas / Estudios --}}
            <div class="space-y-4">

                <x-ui.card size="full" x-data="{ open: true }">
                    <x-ui.heading class="flex pb-2 cursor-pointer select-none" level="h3" size="sm" x-on:click="open = !open">
                        <x-ui.icon name="calendar" class="self-center" />
                        <x-ui.text class="ml-2 text-base flex-1">Consultas / Estudios</x-ui.text>
                        <x-ui.icon name="chevron-up" class="self-center w-4 h-4 transition-transform" x-bind:class="open ? '' : 'rotate-180'" />
                    </x-ui.heading>

                    <div x-show="open" x-collapse>
                        <button wire:click="openUploadForm('{{ \App\Enums\ExternalServicesType::Prescription->value }}')"
                                class="flex items-center gap-1 text-sm text-blue-600 font-medium hover:underline mb-3">
                            <x-ui.icon name="arrow-up-tray" class="w-4 h-4" />
                            Subir archivo
                        </button>

                        <div class="flex flex-col justify-center p-3 bg-white rounded-2xl shadow-sm border border-white/50">
                            @if($appointments->isEmpty() && $externalServices->where('type', \App\Enums\ExternalServicesType::Prescription)->isEmpty())
                                <x-ui.text class="text-base">No hay consultas / estudios</x-ui.text>
                            @endif

                            @foreach($appointments as $record)
                                <div class="w-full grid grid-cols-[2rem_auto] justify-stretch items-center mt-1 mb-1">
                                    <x-ui.icon name="calendar" />
                                    <div class="flex flex-col justify-start ml-1">
                                        <x-ui.text class="text-sm font-semibold">{{$record->date->format('d/m/Y')}}</x-ui.text>
                                        @foreach($record->services as $service)
                                            @if($service->status === 'Completed')
                                                <x-ui.text class="text-sm">{{$service->name}}</x-ui.text>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                                @unless ($loop->last)
                                    <x-ui.separator />
                                @endunless
                            @endforeach

                            @foreach($externalServices->where('type', \App\Enums\ExternalServicesType::Prescription) as $ext)
                                @if($appointments->isNotEmpty() || !$loop->first)
                                    <x-ui.separator />
                                @endif
                                <div class="w-full grid grid-cols-[2rem_auto] justify-stretch items-center mt-1 mb-1">
                                    <x-ui.icon name="paper-clip" />
                                    <div class="flex flex-col justify-start ml-1">
                                        <x-ui.text class="text-sm font-semibold">{{$ext->date->format('d/m/Y')}}</x-ui.text>
                                        @if($ext->attachment_path)
                                            <a href="{{ route('external-service.download', $ext->id) }}">
                                                <x-ui.text class="text-sm">{{$ext->name}}</x-ui.text>
                                            </a>
                                        @else
                                            <x-ui.text class="text-sm">{{$ext->name}}</x-ui.text>
                                        @endif
                                        @if($ext->comments)
                                            <x-ui.text class="text-xs text-gray-500">{{$ext->comments}}</x-ui.text>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </x-ui.card>

            </div>

            {{-- Columna 3: Imagenología (sticky) --}}
            <div class="space-y-4">

                <x-ui.card size="full" class="xl:sticky xl:top-6" x-data="{ open: true }">
                    <x-ui.heading class="flex pb-2 cursor-pointer select-none" level="h3" size="sm" x-on:click="open = !open">
                        <x-ui.icon name="paper-clip" class="self-center" />
                        <x-ui.text class="ml-2 text-base flex-1">Imagenología y estudios</x-ui.text>
                        <x-ui.icon name="chevron-up" class="self-center w-4 h-4 transition-transform" x-bind:class="open ? '' : 'rotate-180'" />
                    </x-ui.heading>

                    <div x-show="open" x-collapse>
                        <button wire:click="openUploadForm('{{ \App\Enums\ExternalServicesType::Analysis->value }}')"
                                class="flex items-center gap-1 text-sm text-blue-600 font-medium hover:underline mb-3">
                            <x-ui.icon name="arrow-up-tray" class="w-4 h-4" />
                            Subir archivo
                        </button>

                        <div class="flex flex-col justify-center p-3 bg-white rounded-2xl shadow-sm border border-white/50">
                            @if($exams->isEmpty() && $externalServices->where('type', \App\Enums\ExternalServicesType::Analysis)->isEmpty())
                                <x-ui.text class="text-base">No hay estudios</x-ui.text>
                            @endif

                            @foreach($exams as $record)
                                <div class="w-full grid grid-cols-[2rem_auto] justify-stretch items-center mt-1 mb-1">
                                    <x-ui.icon name="paper-clip" />
                                    <div class="flex flex-col justify-start ml-1">
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

                            @foreach($externalServices->where('type', \App\Enums\ExternalServicesType::Analysis) as $ext)
                                @if($exams->isNotEmpty() || !$loop->first)
                                    <x-ui.separator />
                                @endif
                                <div class="w-full grid grid-cols-[2rem_auto] justify-stretch items-center mt-1 mb-1">
                                    <x-ui.icon name="paper-clip" />
                                    <div class="flex flex-col justify-start ml-1">
                                        <x-ui.text class="text-sm font-semibold">{{$ext->date->format('d/m/Y')}}</x-ui.text>
                                        @if($ext->attachment_path)
                                            <a href="{{ route('external-service.download', $ext->id) }}">
                                                <x-ui.text class="text-sm">{{$ext->name}}</x-ui.text>
                                            </a>
                                        @else
                                            <x-ui.text class="text-sm">{{$ext->name}}</x-ui.text>
                                        @endif
                                        @if($ext->comments)
                                            <x-ui.text class="text-xs text-gray-500">{{$ext->comments}}</x-ui.text>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </x-ui.card>

            </div>

        </div>
    </div>

    {{-- Upload Form Modal --}}
    @includeWhen($showUploadForm, 'livewire.mobile.user.partials.upload-form-modal')
</div>
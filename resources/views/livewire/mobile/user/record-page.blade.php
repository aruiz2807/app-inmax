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
                {{-- Consultas / Estudios --}}
                <x-ui.accordion.item expanded>
                    <div class="flex w-full items-center">
                        <button x-on:click="toggle()" x-bind:aria-expanded="isVisible"
                                class="flex flex-1 items-center gap-2 justify-start px-6 py-4 cursor-pointer dark:text-white text-gray-800">
                            <span class="flex-1 text-start font-normal text-base">Consultas / Estudios</span>
                            <span style="display: none" x-show="isVisible"><x-ui.icon class="size-5" name="chevron-up" /></span>
                            <span x-show="!isVisible"><x-ui.icon class="size-5" name="chevron-down" /></span>
                        </button>
                    </div>
                    <div style="display: none" x-show="isVisible" x-collapse>
                        <div class="px-6 pb-4 pt-2">
                            <button wire:click="openUploadForm('{{ \App\Enums\ExternalServicesType::Prescription->value }}')"
                                    class="flex items-center gap-1 text-sm text-blue-600 font-medium hover:underline mb-3">
                                <x-ui.icon name="arrow-up-tray" class="w-4 h-4" />
                                Subir archivo
                            </button>
                            <div class="flex flex-col justify-center p-3 bg-[#FFFFFF] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                                @if($appointments->isEmpty() && $externalServices->where('type', \App\Enums\ExternalServicesType::Prescription)->isEmpty())
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
                    </div>
                </x-ui.accordion.item>

                {{-- Diagnosticos y tratamientos --}}
                <x-ui.accordion.item>
                    <div class="flex w-full items-center">
                        <button x-on:click="toggle()" x-bind:aria-expanded="isVisible"
                                class="flex flex-1 items-center gap-2 justify-start px-6 py-4 cursor-pointer dark:text-white text-gray-800">
                            <span class="flex-1 text-start font-normal text-base">Diagnosticos y tratamientos</span>
                            <span style="display: none" x-show="isVisible"><x-ui.icon class="size-5" name="chevron-up" /></span>
                            <span x-show="!isVisible"><x-ui.icon class="size-5" name="chevron-down" /></span>
                        </button>
                    </div>
                    <div style="display: none" x-show="isVisible" x-collapse>
                        <div class="px-6 pb-4 pt-2">
                            <button wire:click="openUploadForm('{{ \App\Enums\ExternalServicesType::Diagnosis->value }}')"
                                    class="flex items-center gap-1 text-sm text-blue-600 font-medium hover:underline mb-3">
                                <x-ui.icon name="arrow-up-tray" class="w-4 h-4" />
                                Subir archivo
                            </button>
                            <div class="flex flex-col justify-center p-3 bg-[#FFFFFF] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                                @if($doctorAppointments->isEmpty() && $externalServices->where('type', \App\Enums\ExternalServicesType::Diagnosis)->isEmpty())
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
                    </div>
                </x-ui.accordion.item>

                {{-- Imagenología y estudios --}}
                <x-ui.accordion.item>
                    <div class="flex w-full items-center">
                        <button x-on:click="toggle()" x-bind:aria-expanded="isVisible"
                                class="flex flex-1 items-center gap-2 justify-start px-6 py-4 cursor-pointer dark:text-white text-gray-800">
                            <span class="flex-1 text-start font-normal text-base">Imagenología y estudios</span>
                            <span style="display: none" x-show="isVisible"><x-ui.icon class="size-5" name="chevron-up" /></span>
                            <span x-show="!isVisible"><x-ui.icon class="size-5" name="chevron-down" /></span>
                        </button>
                    </div>
                    <div style="display: none" x-show="isVisible" x-collapse>
                        <div class="px-6 pb-4 pt-2">
                            <button wire:click="openUploadForm('{{ \App\Enums\ExternalServicesType::Analysis->value }}')"
                                    class="flex items-center gap-1 text-sm text-blue-600 font-medium hover:underline mb-3">
                                <x-ui.icon name="arrow-up-tray" class="w-4 h-4" />
                                Subir archivo
                            </button>
                            <div class="flex flex-col justify-center p-3 bg-[#FFFFFF] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                                @if($exams->isEmpty() && $externalServices->where('type', \App\Enums\ExternalServicesType::Analysis)->isEmpty())
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
                    </div>
                </x-ui.accordion.item>

                {{-- Vacunas --}}
                <x-ui.accordion.item>
                    <div class="flex w-full items-center">
                        <button x-on:click="toggle()" x-bind:aria-expanded="isVisible"
                                class="flex flex-1 items-center gap-2 justify-start px-6 py-4 cursor-pointer dark:text-white text-gray-800">
                            <span class="flex-1 text-start font-normal text-base">Vacunas</span>
                            <span style="display: none" x-show="isVisible"><x-ui.icon class="size-5" name="chevron-up" /></span>
                            <span x-show="!isVisible"><x-ui.icon class="size-5" name="chevron-down" /></span>
                        </button>
                    </div>
                    <div style="display: none" x-show="isVisible" x-collapse>
                        <div class="px-6 pb-4 pt-2">
                            <button wire:click="openUploadForm('{{ \App\Enums\ExternalServicesType::Vaccine->value }}')"
                                    class="flex items-center gap-1 text-sm text-blue-600 font-medium hover:underline mb-3">
                                <x-ui.icon name="arrow-up-tray" class="w-4 h-4" />
                                Subir archivo
                            </button>
                            <div class="flex flex-col justify-center p-3 bg-[#FFFFFF] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
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
                    </div>
                </x-ui.accordion.item>
            </x-ui.accordion>
        </x-ui.card>
    </div>

    {{-- Upload Form Modal --}}
    @if($showUploadForm)
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
         x-data
         x-on:keydown.escape.window="$wire.closeUploadForm()">
        <div class="w-full max-w-lg mx-4 bg-white rounded-2xl shadow-xl p-5 pb-8">
            <div class="flex justify-between items-center mb-4">
                <x-ui.text class="text-lg font-semibold">Importar archivo</x-ui.text>
                <button wire:click="closeUploadForm" class="text-gray-400 hover:text-gray-600">
                    <x-ui.icon name="x-mark" class="w-5 h-5" />
                </button>
            </div>

            <form wire:submit.prevent="saveExternalService" class="flex flex-col gap-3">
                {{-- Fecha estudio --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Fecha estudio</label>
                    <input type="date"
                           wire:model="uploadDate"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" />
                    @error('uploadDate') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Título --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Título <span class="text-red-500">*</span></label>
                    <input type="text"
                           wire:model="uploadName"
                           placeholder="Ingresa un título"
                           class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400" />
                    @error('uploadName') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Comentarios --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Comentarios</label>
                    <textarea wire:model="uploadComments"
                              placeholder="Comentarios opcionales..."
                              rows="3"
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400"></textarea>
                    @error('uploadComments') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                {{-- Archivo --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Archivo</label>
                    <input type="file"
                           wire:model="uploadFile"
                           placeholder="Seleccione un archivo para adjuntar"
                           class="w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" />
                    <x-ui.error name="uploadFile" />
                    <div wire:loading wire:target="uploadFile">
                        Subiendo archivo...
                    </div>
                </div>

                <div class="flex gap-3 mt-2">
                    <button type="button" wire:click="closeUploadForm"
                        class="flex-1 py-2 rounded-xl border border-gray-300 text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="flex-1 py-2 rounded-xl bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>

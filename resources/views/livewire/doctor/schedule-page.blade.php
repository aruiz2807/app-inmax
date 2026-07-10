<div class="space-y-4" x-data="{ customServiceModal: false }" @close-custom-service-modal.window="customServiceModal = false">
    <x-slot name="header">
        Programar consulta
    </x-slot>

    <x-validation-errors class="mb-4" />

    {{-- Info del paciente --}}
    <x-ui.card size="full">
        <div class="flex items-center gap-4">
            <x-ui.avatar size="xl" icon="user" color="teal" :src="$appointment->user->photo_url" circle />
            <div>
                <x-ui.text class="text-xl">{{ $appointment->user->name }}</x-ui.text>
                <x-ui.badge :icon="$appointment->user->policy->status_icon" variant="outline" :color="$appointment->user->policy->status_color" pill>
                    {{ $appointment->user->policy->status_text }}
                </x-ui.badge>
            </div>
        </div>
    </x-ui.card>

    <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">

        {{-- Columna 1: Médico, Consultorio y Servicios --}}
        <div class="space-y-4">

            <x-ui.card size="full">
                <x-ui.heading class="flex pb-2" level="h3" size="sm">
                    <x-ui.icon name="user" class="self-center" />
                    <x-ui.text class="ml-2 text-base">Médico / Proveedor</x-ui.text>
                </x-ui.heading>

                <x-ui.field>
                    <x-ui.select
                        placeholder="Seleccionar medico o proveedor..."
                        icon="wallet"
                        searchable
                        wire:model.live="selectedDoctor">
                        @foreach($doctors as $doctor)
                            <x-ui.select.option value="{{ $doctor->id }}">
                                {{ $doctor->user->name }} - {{ $doctor->specialty->name }}
                            </x-ui.select.option>
                        @endforeach
                    </x-ui.select>
                    <x-ui.error name="selectedDoctor" />
                </x-ui.field>
            </x-ui.card>

            @if($selectedDoctor && $offices->count())
            <x-ui.card size="full">
                <x-ui.heading class="flex pb-2" level="h3" size="sm">
                    <x-ui.icon name="building-office" class="self-center" />
                    <x-ui.text class="ml-2 text-base">Consultorio</x-ui.text>
                </x-ui.heading>

                <x-ui.field>
                    <x-ui.select
                        placeholder="Buscar consultorio..."
                        icon="wallet"
                        searchable
                        wire:model.live="selectedOffice">
                        @foreach($offices as $office)
                            <x-ui.select.option value="{{ $office->id }}">
                                {{ $office->name }}
                            </x-ui.select.option>
                        @endforeach
                    </x-ui.select>
                    <x-ui.error name="selectedOffice" />
                </x-ui.field>
            </x-ui.card>
            @endif

            @if($selectedDoctor)
            <x-ui.card size="full">
                <x-ui.heading class="flex pb-2" level="h3" size="sm">
                    <x-ui.icon name="clipboard-document-list" class="self-center" />
                    <div class="flex flex-1 items-center justify-between ml-2">
                        <x-ui.text class="text-base">Servicios</x-ui.text>
                        <x-ui.button wire:key="btn-add-custom-service" x-on:click="customServiceModal = true" size="xs" variant="outline" color="blue" icon="plus">
                            Otro servicio
                        </x-ui.button>
                    </div>
                </x-ui.heading>

                <x-ui.field>
                    <x-ui.select
                        wire:key="services-select-{{ $selectedDoctor }}"
                        placeholder="Buscar servicio..."
                        icon="wallet"
                        searchable
                        multiple
                        clearable
                        search-emit="service-search-changed"
                        x-on:service-search-changed.debounce.300ms="$wire.set('serviceSearch', $event.detail.search)"
                        load-more-emit="load-more-services"
                        x-on:load-more-services.debounce.200ms="$wire.loadMoreServices()"
                        wire:model.live="selectedServices">
                        @foreach($this->services as $service)
                            <x-ui.select.option value="{{ $service->id }}" wire:key="service-opt-{{ $service->id }}-{{ $selectedDoctor }}">
                                {{ $service->name }}
                            </x-ui.select.option>
                        @endforeach
                    </x-ui.select>
                    <x-ui.error name="selectedServices" />
                    <x-ui.error name="unregisteredServices" />
                </x-ui.field>

                @if($this->servicesData)
                    <div class="flex flex-col gap-2 mt-4">
                        @foreach($this->servicesData as $service)
                            <div class="grid grid-cols-[5rem_auto_8rem] items-center p-4 bg-[#E3F2FD] rounded-2xl shadow-sm hover:shadow-md transition-shadow border border-white/50">
                                <div class="p-3 bg-[#2D4356] rounded-xl text-white mr-4">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="#FFFFFF" viewBox="0 0 256 256">
                                        <path d="M220,160a12,12,0,1,1-12-12A12,12,0,0,1,220,160Zm-4.55,39.29A48.08,48.08,0,0,1,168,240H144a48.05,48.05,0,0,1-48-48V151.49A64,64,0,0,1,40,88V40a8,8,0,0,1,8-8H72a8,8,0,0,1,0,16H56V88a48,48,0,0,0,48.64,48c26.11-.34,47.36-22.25,47.36-48.83V48H136a8,8,0,0,1,0-16h24a8,8,0,0,1,8,8V87.17c0,32.84-24.53,60.29-56,64.31V192a32,32,0,0,0,32,32h24a32.06,32.06,0,0,0,31.22-25,40,40,0,1,1,16.23.27ZM232,160a24,24,0,1,0-24,24A24,24,0,0,0,232,160Z"></path>
                                    </svg>
                                </div>
                                <div class="flex flex-col">
                                    <div class="flex items-center gap-2">
                                        <x-ui.text class="text-lg font-semibold">{{ $service['name'] }}</x-ui.text>
                                        @if($service['unregistered_service'])
                                            <x-ui.button wire:click="removeUnregisteredService({{ $service['index'] }})" size="xs" variant="ghost" color="red" icon="trash" />
                                        @endif
                                    </div>
                                    @if($service['included'])
                                        <x-ui.text class="text-sm opacity-50">Servicio incluido</x-ui.text>
                                    @elseif($service['unregistered_service'])
                                        <x-ui.text class="text-sm opacity-50">Servicio externo / no registrado</x-ui.text>
                                    @else
                                        <x-ui.text class="text-sm opacity-50">Servicio adicional</x-ui.text>
                                    @endif
                                </div>
                                @if($service['included'])
                                    <x-ui.badge class="ml-8" icon="check-circle" variant="outline" color="green" pill>Cubierta</x-ui.badge>
                                @else
                                    <x-ui.badge class="ml-8" icon="exclamation-triangle" variant="outline" color="yellow" pill>Adicional</x-ui.badge>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </x-ui.card>
            @endif

        </div>

        {{-- Columna 2: Fechas disponibles --}}
        <div class="space-y-4">

            @if($selectedDoctor)
            <x-ui.card size="full">
                <x-ui.heading class="flex pb-2" level="h3" size="sm">
                    <x-ui.icon name="calendar" variant="solid" class="self-center" />
                    <x-ui.text class="ml-2 text-base">Fechas disponibles</x-ui.text>
                </x-ui.heading>

                <div class="grid grid-cols-3 gap-3 mt-2" wire:key="dates-{{ $selectedDate }}">
                    @foreach($this->availableDates as $date)
                        <label class="group relative cursor-pointer">
                            <input type="radio"
                                wire:model.live="selectedDate"
                                value="{{ $date['id'] }}"
                                class="sr-only peer"
                            >
                            <div class="flex flex-col items-center justify-center py-4 border-2 rounded-xl transition-all duration-200
                                        bg-white border-slate-100
                                        peer-checked:bg-sky-200 peer-checked:border-sky-200 peer-checked:shadow-lg peer-checked:shadow-blue-200
                                        hover:border-sky-100">
                                <span class="text-[10px] font-bold uppercase tracking-tighter mb-1 text-slate-400 peer-checked:text-blue-200">
                                    {{ $date['day'] }}
                                </span>
                                <span class="text-sm font-extrabold text-slate-700 peer-checked:text-white">
                                    {{ $date['num'] }} {{ $date['month'] }}
                                </span>
                            </div>
                        </label>
                    @endforeach
                </div>
            </x-ui.card>
            @endif

        </div>

        {{-- Columna 3: Horarios y acción (sticky) --}}
        <div class="space-y-4">

            @if($selectedDoctor)
            <x-ui.card size="full" class="xl:sticky xl:top-6">
                <x-ui.heading class="flex pb-2" level="h3" size="sm">
                    <x-ui.icon name="clock" variant="solid" class="self-center" />
                    <x-ui.text class="ml-2 text-base">Horarios disponibles</x-ui.text>
                    <div wire:loading wire:target="selectedDate" class="ml-4">
                        <svg class="animate-spin h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </x-ui.heading>

                <div class="grid grid-cols-2 gap-3 mt-2" wire:key="hours-{{ $selectedTime }}">
                    @foreach($this->availableHours as $hour)
                        <label class="group relative cursor-pointer">
                            <input type="radio"
                                wire:model.live="selectedTime"
                                value="{{ $hour['id'] }}"
                                class="sr-only peer"
                            >
                            <div class="flex flex-col items-center justify-center py-4 border-2 rounded-xl transition-all duration-200
                                        bg-white border-slate-100
                                        peer-checked:bg-sky-200 peer-checked:border-sky-200 peer-checked:shadow-lg peer-checked:shadow-blue-200
                                        hover:border-sky-100">
                                <span class="text-sm font-extrabold text-slate-700 peer-checked:text-white">
                                    {{ $hour['time'] }}
                                </span>
                            </div>
                        </label>
                    @endforeach
                </div>

                <div class="flex justify-end border-t border-neutral-200 pt-4 mt-4">
                    <x-ui.button wire:click="schedule" icon="check" color="teal" :disabled="empty($this->servicesData)">
                        Programar consulta
                    </x-ui.button>
                </div>
            </x-ui.card>
            @endif

        </div>

    </div>

    {{-- Custom Service Modal --}}
    <div x-show="customServiceModal"
         class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-900/50 backdrop-blur-sm"
         x-cloak
         x-transition>
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6" @click.away="customServiceModal = false">
            <x-ui.heading level="h3" class="mb-4">Agregar otro servicio</x-ui.heading>

            <x-ui.field>
                <x-ui.label>Descripción del servicio</x-ui.label>
                <x-ui.input wire:model="newUnregisteredService" placeholder="Ej. Radiografía de tórax" />
                <x-ui.error name="newUnregisteredService" />
            </x-ui.field>

            <div class="flex justify-end gap-3 mt-6">
                <x-ui.button x-on:click="customServiceModal = false" variant="outline">
                    Cancelar
                </x-ui.button>
                <x-ui.button wire:click="addUnregisteredService" variant="primary" color="blue">
                    Agregar
                </x-ui.button>
            </div>
        </div>
    </div>

</div>
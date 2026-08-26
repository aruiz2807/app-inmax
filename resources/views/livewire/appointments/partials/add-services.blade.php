<div class="mt-2" wire:key="add-services-section">
    <div class="flex justify-between items-center mb-1">
        <x-ui.label>Añadir servicios</x-ui.label>
        <x-ui.button wire:key="btn-add-custom-service" x-on:click="customServiceModal = true" size="xs" variant="outline" color="blue" icon="plus">
            Otro servicio
        </x-ui.button>
    </div>

    <x-ui.select
        wire:key="services-select-{{ $appointment->doctor_id ?? 'none' }}-{{ $appointment->services->count() }}"
        placeholder="Buscar servicio..."
        icon="wallet"
        searchable
        multiple
        clearable
        search-emit="service-search-changed"
        x-on:service-search-changed.debounce.300ms="$wire.set('serviceSearch', $event.detail.search)"
        load-more-emit="load-more-services"
        x-on:load-more-services.debounce.200ms="$wire.loadMoreServices()"
        wire:model.live="servicesToAdd">
            @foreach($this->availableServices as $service)
                <x-ui.select.option value="{{ $service->id }}" wire:key="service-opt-{{ $service->id }}">
                    {{ $service->name }}
                </x-ui.select.option>
            @endforeach
    </x-ui.select>
    <x-ui.error name="servicesToAdd" />
    <x-ui.error name="newUnregisteredService" />

    @if($this->addedServicesData)
        <div class="flex flex-col gap-2 mt-4">
            @foreach($this->addedServicesData as $service)
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

<div>
    <div class="relative w-full">
        <img src="/img/top.png" alt="Header" class="w-full object-cover">
    </div>

    <div class="grid grid-cols-[2rem_auto] justify-stretch items-center pt-4 pb-4">
        <x-ui.icon name="arrow-left" class="w-5 h-5 cursor-pointer" x-on:click="window.history.back()" />
        <x-ui.text class="text-2xl">Receta virtual</x-ui.text>
    </div>

    <x-ui.card size="full" class="mx-auto" x-data="{ open: false }" @click.away="open = false">
        <x-ui.heading class="flex pb-2" level="h3" size="sm">
            <x-ui.icon name="user" class="self-center" />
            <x-ui.text class="text-base ml-2">Paciente</x-ui.text>
        </x-ui.heading>

        @if($this->selectedPatient)
            <div class="flex items-center justify-between">
                <div class="flex mt-1">
                    <x-ui.avatar size="lg" icon="user" color="teal" :src="$this->selectedPatient->photo_url" circle />
                    <div class="pl-4">
                        <x-ui.text class="pt-1 text-lg">{{ $this->selectedPatient->name }}</x-ui.text>
                        <x-ui.text class="text-sm opacity-75">{{ $this->selectedPatient->policy->number }}</x-ui.text>
                    </div>
                </div>
                <x-ui.button wire:click="clearPatient" icon="x-mark" variant="outline" color="red" size="sm" />
            </div>
        @else
            <x-ui.field class="relative">
                <x-ui.label>Buscar paciente</x-ui.label>
                <x-ui.input
                    wire:model.live.debounce.300ms="patientSearch"
                    placeholder="Busque por nombre o número de póliza..."
                    @focus="open = true"
                    @input="open = true"
                />

                <div
                    x-show="open"
                    class="absolute z-50 w-full bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-lg shadow-lg mt-1 max-h-60 overflow-y-auto"
                >
                    @forelse($this->patients as $patient)
                        <div
                            wire:click="selectPatient({{ $patient->id }})"
                            @click="open = false"
                            class="p-2 hover:bg-neutral-100 dark:hover:bg-neutral-700 cursor-pointer border-b border-neutral-100 dark:border-neutral-700 last:border-0"
                        >
                            <x-ui.text class="font-bold text-sm">{{ $patient->name }}</x-ui.text>
                            <x-ui.text class="text-xs opacity-75">{{ $patient->policy->number }}</x-ui.text>
                        </div>
                    @empty
                        <div class="p-4 text-center">
                            <x-ui.text class="text-sm opacity-50">No se encontraron pacientes</x-ui.text>
                        </div>
                    @endforelse
                </div>

                <x-ui.error name="selectedPatientId" />
            </x-ui.field>
        @endif
    </x-ui.card>

    <x-ui.card size="full" class="mx-auto mt-2">
        <x-ui.heading class="flex pb-2" level="h3" size="sm">
            <x-ui.icon name="clipboard-document-list" class="self-center" />
            <x-ui.text class="text-base ml-2">Medicamentos</x-ui.text>
        </x-ui.heading>

        <div class="flex flex-col gap-2">
            <x-ui.field class="relative" x-data="{ open: false }" @click.away="open = false">
                <x-ui.label>Medicamento</x-ui.label>
                <x-ui.input
                    wire:model.live.debounce.300ms="searchTerm"
                    placeholder="Busque un medicamento..."
                    @focus="open = true"
                    @input="open = true"
                />

                <div
                    x-show="open && $wire.searchTerm.length > 0"
                    class="absolute z-50 w-full bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-neutral-700 rounded-lg shadow-lg mt-1 max-h-60 overflow-y-auto"
                >
                    @forelse($this->medications as $medication)
                        <div
                            wire:click="selectMedication({{ $medication->id }}, '{{ $medication->name }}')"
                            @click="open = false"
                            class="p-2 hover:bg-neutral-100 dark:hover:bg-neutral-700 cursor-pointer border-b border-neutral-100 dark:border-neutral-700 last:border-0"
                        >
                            <div class="flex items-center gap-2">
                                <x-ui.text class="font-bold text-sm">{{ $medication->active_substance }}</x-ui.text>
                                @if($medication->existences > 0)
                                    <x-ui.badge color="green" pill size="sm">
                                        {{ (int) $medication->existences }}
                                    </x-ui.badge>
                                @else
                                    <x-ui.badge color="red" pill size="sm">
                                        0
                                    </x-ui.badge>
                                @endif
                            </div>
                            <x-ui.text class="text-xs opacity-75">{{ $medication->packaging }} ({{ $medication->trade_name }})</x-ui.text>
                        </div>
                    @empty
                        <div class="p-4 text-center">
                            <x-ui.text class="text-sm opacity-50">No se encontraron medicamentos</x-ui.text>
                        </div>
                    @endforelse
                </div>

                <x-ui.error name="medicationId" />
            </x-ui.field>

            <div class="grid grid-cols-2 gap-2">
                <x-ui.field>
                    <x-ui.label>Dosis</x-ui.label>
                    <x-ui.input wire:model="quantity" />
                    <x-ui.error name="quantity" />
                </x-ui.field>
                <x-ui.field>
                    <x-ui.label>Forma</x-ui.label>
                    <x-ui.input wire:model="dose" placeholder="Ej. Tableta" />
                    <x-ui.error name="dose" />
                </x-ui.field>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <x-ui.field>
                    <x-ui.label>Frecuencia</x-ui.label>
                    <x-ui.input wire:model="frequency" placeholder="Ej. 8 horas" />
                    <x-ui.error name="frequency" />
                </x-ui.field>
                <x-ui.field>
                    <x-ui.label>Duración</x-ui.label>
                    <x-ui.input wire:model="duration" placeholder="Ej. 7 días" />
                    <x-ui.error name="duration" />
                </x-ui.field>
            </div>

            <x-ui.button wire:click="addMedication" color="teal" icon="plus" class="w-full mt-2">
                Agregar a receta
            </x-ui.button>
        </div>

        @if(count($prescriptions) > 0)
            <div class="mt-4 border-t pt-2">
                <x-ui.text class="font-semibold mb-2">Medicamentos recetados:</x-ui.text>
                <div class="flex flex-col gap-2">
                    @foreach($prescriptions as $index => $prescription)
                        <div class="bg-gray-50 p-2 rounded-lg flex justify-between items-center shadow-sm border border-gray-100">
                            <div class="flex-1">
                                <x-ui.text class="font-bold text-sm">{{ $prescription['name'] }}</x-ui.text>
                                <x-ui.text class="text-xs text-gray-600">
                                    {{ $prescription['quantity'] }} • {{ $prescription['dose'] }} • {{ $prescription['frequency'] }} • {{ $prescription['duration'] }}
                                </x-ui.text>
                            </div>
                            <x-ui.button wire:click="removePrescription({{ $index }})" icon="trash" variant="danger" size="sm" class="ml-2" />
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <x-ui.error name="prescriptions" />
    </x-ui.card>

    <div class="flex justify-center mt-4">
        <x-ui.button class="w-40 mr-1" wire:click="save" variant="outline" color="blue" icon="clipboard">
            Guardar
        </x-ui.button>
    </div>
</div>

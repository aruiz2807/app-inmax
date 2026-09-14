<div class="space-y-4">
    <x-slot name="header">
        Receta virtual
    </x-slot>

    <x-ui.card size="full">
        <x-ui.heading class="flex items-center justify-between mb-4" level="h3" size="sm">
            <span>Recetas</span>

            <x-ui.modal.trigger id="virtual-notes-modal" wire:click="resetVirtualForm">
                <x-ui.button color="teal" icon="plus-circle">
                    Nueva receta
                </x-ui.button>
            </x-ui.modal.trigger>
        </x-ui.heading>

        <p>Genera recetas sin necesidad de una consulta, seleccionando un paciente y sus medicamentos</p>
    </x-ui.card>

    <div class="grid grid-cols-1 gap-4 pt-2 md:grid-cols-3">
        <x-ui.card size="full" class="border-t-2 border-yellow-400">
            <p class="text-xs font-semibold tracking-wide uppercase text-neutral-500">Pendientes</p>
            <p class="mt-2 text-3xl font-bold text-neutral-900">{{ $this->pendingCount }}</p>
            <p class="text-xs text-neutral-500">Por surtir</p>
        </x-ui.card>

        <x-ui.card size="full" class="border-t-2 border-blue-500">
            <p class="text-xs font-semibold tracking-wide uppercase text-neutral-500">Parciales</p>
            <p class="mt-2 text-3xl font-bold text-neutral-900">{{ $this->partialCount }}</p>
            <p class="text-xs text-neutral-500">Con entrega incompleta</p>
        </x-ui.card>

        <x-ui.card size="full" class="border-t-2 border-green-500">
            <p class="text-xs font-semibold tracking-wide uppercase text-neutral-500">Surtidas</p>
            <p class="mt-2 text-3xl font-bold text-neutral-900">{{ $this->filledCount }}</p>
            <p class="text-xs text-neutral-500">Entregadas por completo</p>
        </x-ui.card>
    </div>

    <div class="pt-2">
        <x-ui.card size="full">
            <div class="flex gap-2 mb-4 border-b border-neutral-200">
                <button
                    type="button"
                    wire:click="setTab('pending')"
                    @class([
                        'px-4 py-2 text-sm font-medium transition-colors',
                        'border-b-2 border-teal-600 text-teal-600' => $tab === 'pending',
                        'text-neutral-600 hover:text-neutral-900' => $tab !== 'pending',
                    ])
                >
                    Pendientes
                </button>

                <button
                    type="button"
                    wire:click="setTab('partial')"
                    @class([
                        'px-4 py-2 text-sm font-medium transition-colors',
                        'border-b-2 border-teal-600 text-teal-600' => $tab === 'partial',
                        'text-neutral-600 hover:text-neutral-900' => $tab !== 'partial',
                    ])
                >
                    Parciales
                </button>

                <button
                    type="button"
                    wire:click="setTab('filled')"
                    @class([
                        'px-4 py-2 text-sm font-medium transition-colors',
                        'border-b-2 border-teal-600 text-teal-600' => $tab === 'filled',
                        'text-neutral-600 hover:text-neutral-900' => $tab !== 'filled',
                    ])
                >
                    Surtidas
                </button>

                <button
                    type="button"
                    wire:click="setTab('all')"
                    @class([
                        'px-4 py-2 text-sm font-medium transition-colors',
                        'border-b-2 border-teal-600 text-teal-600' => $tab === 'all',
                        'text-neutral-600 hover:text-neutral-900' => $tab !== 'all',
                    ])
                >
                    Todas
                </button>
            </div>

            <livewire:doctor.virtual-notes-table :tab="$tab" :key="'virtual-notes-table-'.$tab" />
        </x-ui.card>
    </div>

    <x-ui.modal
        id="virtual-notes-modal"
        animation="fade"
        width="2xl"
        heading="Nueva receta virtual"
        description="Selecciona un paciente y sus medicamentos para generar la receta"
        x-on:close-virtual-notes-modal.window="$data.close()"
        x-on:open-virtual-notes-modal.window="$data.open()"
    >
        <div class="grid grid-cols-1 gap-4 xl:grid-cols-12">
            <div class="space-y-4 xl:col-span-12">
                <x-ui.card size="full" x-data="{ open: false }" @click.away="open = false">
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
            </div>

            <div class="space-y-4 xl:col-span-12">
                <x-ui.card size="full">
                    <x-ui.heading class="flex pb-2" level="h3" size="sm">
                        <x-ui.icon name="clipboard-document-list" class="self-center" />
                        <x-ui.text class="text-base ml-2">Medicamentos</x-ui.text>
                    </x-ui.heading>

                    <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                        <div class="lg:col-span-2">
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
                        </div>

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

                        <x-ui.field>
                            <x-ui.label>Frecuencia</x-ui.label>
                            <x-ui.input wire:model="frequency" placeholder="Ej. 8 horas" />
                            <x-ui.error name="frequency" />
                        </x-ui.field>

                        <x-ui.field>
                            <x-ui.label>Duracion</x-ui.label>
                            <x-ui.input wire:model="duration" placeholder="Ej. 7 dias" />
                            <x-ui.error name="duration" />
                        </x-ui.field>
                    </div>

                    <x-ui.button wire:click="addMedication" color="teal" icon="plus" class="w-full mt-2">
                        Agregar a receta
                    </x-ui.button>

                    @if(count($prescriptions) > 0)
                        <div class="mt-4 border-t pt-2">
                            <x-ui.text class="font-semibold mb-2">Medicamentos recetados:</x-ui.text>
                            <div class="flex flex-col gap-2">
                                @foreach($prescriptions as $index => $prescription)
                                    <div class="bg-gray-50 p-2 rounded-lg flex justify-between items-center shadow-sm border border-gray-100">
                                        <div class="flex-1">
                                            <x-ui.text class="font-bold text-sm">{{ $prescription['name'] }}</x-ui.text>
                                            <x-ui.text class="text-xs text-gray-600">
                                                {{ $prescription['quantity'] }} - {{ $prescription['dose'] }} - {{ $prescription['frequency'] }} - {{ $prescription['duration'] }}
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
            </div>
        </div>

        <div class="w-full flex justify-end gap-3 pt-4">
            <x-ui.button x-on:click="$data.close();" icon="x-mark" variant="outline">
                Cancelar
            </x-ui.button>

            <x-ui.button wire:click="save" icon="check" variant="primary" color="teal">
                Guardar
            </x-ui.button>
        </div>
    </x-ui.modal>

    <x-ui.modal
        id="virtual-note-details-modal"
        animation="fade"
        width="lg"
        heading="Detalle de receta"
        description="{{ $noteDetails['patient_name'] ?? '' }}"
        x-on:close-virtual-note-details-modal.window="$data.close()"
        x-on:open-virtual-note-details-modal.window="$data.open()"
    >
        @if($noteDetails)
            <div class="space-y-3">
                <div class="flex justify-between text-sm text-neutral-600">
                    <span>Membresia: {{ $noteDetails['membership_number'] }}</span>
                    <span>Fecha: {{ $noteDetails['date_label'] }}</span>
                </div>

                <div class="flex flex-col gap-2">
                    @forelse($noteDetails['prescriptions'] as $prescription)
                        <div class="bg-gray-50 p-2 rounded-lg shadow-sm border border-gray-100">
                            <x-ui.text class="font-bold text-sm">{{ $prescription['name'] }}</x-ui.text>
                            <x-ui.text class="text-xs text-gray-600">
                                {{ $prescription['quantity'] }} - {{ $prescription['dose'] }} - {{ $prescription['frequency'] }} - {{ $prescription['duration'] }}
                            </x-ui.text>
                        </div>
                    @empty
                        <p class="text-sm text-neutral-500 italic">Sin medicamentos registrados.</p>
                    @endforelse
                </div>
            </div>
        @endif

        <div class="w-full flex justify-end pt-4">
            <x-ui.button x-on:click="$data.close();" icon="x-mark" variant="outline">
                Cerrar
            </x-ui.button>
        </div>
    </x-ui.modal>
</div>

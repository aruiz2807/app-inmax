<x-ui.modal
    id="transport-modal"
    animation="fade"
    width="5xl"
    heading="Nuevo traslado"
    description="Complete la información para registrar un nuevo traslado"
    x-on:close-transport-modal.window="$data.close()"
>
    <div class="space-y-6">
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            <x-ui.field class="lg:col-span-2">
                <x-ui.label>Paciente</x-ui.label>
                <x-ui.select
                    placeholder="Buscar por nombre o número de membresía..."
                    icon="magnifying-glass"
                    searchable
                    clearable
                    search-emit="transport-patient-search"
                    x-on:transport-patient-search.debounce.300ms="$wire.set('patientSearch', $event.detail.search)"
                    wire:model.live="selectedPatientId"
                >
                    @foreach($this->patients as $patient)
                        <x-ui.select.option value="{{ $patient->id }}">
                            {{ $patient->name }} - {{ $patient->policy?->number }}
                        </x-ui.select.option>
                    @endforeach
                </x-ui.select>
                <x-ui.error name="selectedPatientId" />
            </x-ui.field>

            @if($this->selectedPatient)
                <x-ui.card size="full" class="lg:col-span-2">
                    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                        <div>
                            <x-ui.text class="text-lg font-semibold">{{ $this->selectedPatient->name }}</x-ui.text>
                            <x-ui.text class="text-sm text-neutral-500">{{ $this->selectedPatient->policy?->number }}</x-ui.text>
                        </div>

                        @if($this->selectedPatient->policy)
                            <x-ui.badge :icon="$this->selectedPatient->policy->status_icon" variant="outline" :color="$this->selectedPatient->policy->status_color" pill>
                                {{ $this->selectedPatient->policy->status_text }}
                            </x-ui.badge>
                        @endif
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                        <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-3">
                            <x-ui.text class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Edad</x-ui.text>
                            <x-ui.text class="mt-1 font-semibold">{{ $this->selectedPatient->age ?? 'N/D' }}</x-ui.text>
                        </div>

                        <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-3">
                            <x-ui.text class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Teléfono</x-ui.text>
                            <x-ui.text class="mt-1 font-semibold">{{ $this->selectedPatient->phone }}</x-ui.text>
                        </div>

                        <div class="rounded-xl border border-neutral-200 bg-neutral-50 p-3">
                            <x-ui.text class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Membresía</x-ui.text>
                            <x-ui.text class="mt-1 font-semibold">{{ $this->selectedPatient->policy?->number ?? 'Sin membresía' }}</x-ui.text>
                        </div>
                    </div>
                </x-ui.card>
            @endif
            
            <x-ui.field class="lg:col-span-2">
                <x-ui.label>Tipo de traslado</x-ui.label>
                <x-ui.select wire:model.live="transportType" placeholder="Selecciona un tipo..." icon="truck" searchable>
                    <x-ui.select.option value="programado">Programado</x-ui.select.option>
                    <x-ui.select.option value="urgencia">Urgencia</x-ui.select.option>
                    <x-ui.select.option value="cuidados_avanzados">Cuidados avanzados</x-ui.select.option>
                </x-ui.select>
                <x-ui.error name="transportType" />
            </x-ui.field>

            <x-ui.field class="lg:col-span-1">
                <x-ui.label>Origen</x-ui.label>
                <x-ui.input wire:model.live="origin" placeholder="Escribe el origen del traslado" />
                <x-ui.error name="origin" />
            </x-ui.field>

            <x-ui.field class="lg:col-span-1">
                <x-ui.label>Destino</x-ui.label>
                <x-ui.select wire:model.live="destinationSelection" placeholder="Buscar hospital o elegir otra dirección..." icon="building-office" searchable>
                    @foreach($this->hospitals as $hospital)
                        <x-ui.select.option value="{{ $hospital->id }}">
                            {{ $hospital->name }} - {{ $hospital->address }}
                        </x-ui.select.option>
                    @endforeach

                    <x-ui.select.option value="custom">
                        Otra dirección
                    </x-ui.select.option>
                </x-ui.select>
                <x-ui.error name="destinationSelection" />
            </x-ui.field>

            @if($destinationSelection === 'custom')
                <x-ui.field class="lg:col-span-2">
                    <x-ui.label>Dirección del destino</x-ui.label>
                    <x-ui.input wire:model.live="customDestinationAddress" placeholder="Escribe la dirección completa" />
                    <x-ui.error name="customDestinationAddress" />
                </x-ui.field>
            @endif

            <div class="lg:col-span-2">
                <x-ui.fieldset label="Servicios">
                    <x-ui.checkbox.group wire:model="selectedServiceIds" variant="cards" class="grid grid-cols-1 gap-3 md:grid-cols-2">
                        @foreach($this->services as $service)
                            <x-ui.checkbox
                                value="{{ $service->id }}"
                                label="{{ $service->name }}"
                                description="{{ $service->type === 'Amount' ? 'Importe' : 'Evento' }}"
                            />
                        @endforeach
                    </x-ui.checkbox.group>
                    <x-ui.error name="selectedServiceIds" />
                </x-ui.fieldset>
            </div>

            <x-ui.card size="full" class="lg:col-span-2">
                <x-ui.heading class="flex items-center justify-between mb-4" level="h3" size="sm">
                    <span>Evaluación de severidad</span>
                    <x-ui.badge variant="outline" color="slate" pill>Sin evaluar</x-ui.badge>
                </x-ui.heading>

                <div class="space-y-5">
                    <div>
                        <x-ui.text class="mb-2 text-base font-semibold">¿El paciente está consciente?</x-ui.text>
                        <div class="grid grid-cols-2 gap-3">
                            <x-ui.button
                                type="button"
                                variant="outline"
                                color="zinc"
                                class="w-full justify-center"
                                @class([
                                    '!border-teal-500 !bg-teal-50 !text-teal-700' => $severityConscious === true,
                                ])
                                wire:click="$set('severityConscious', true)"
                            >
                                Sí
                            </x-ui.button>

                            <x-ui.button
                                type="button"
                                variant="outline"
                                color="zinc"
                                class="w-full justify-center"
                                @class([
                                    '!border-teal-500 !bg-teal-50 !text-teal-700' => $severityConscious === false,
                                ])
                                wire:click="$set('severityConscious', false)"
                            >
                                No
                            </x-ui.button>
                        </div>
                    </div>

                    <div>
                        <x-ui.text class="mb-2 text-base font-semibold">¿Respira con dificultad?</x-ui.text>
                        <div class="grid grid-cols-3 gap-3">
                            <x-ui.button
                                type="button"
                                variant="outline"
                                color="zinc"
                                class="w-full justify-center"
                                @class([
                                    '!border-teal-500 !bg-teal-50 !text-teal-700' => $severityBreathing === 'no',
                                ])
                                wire:click="$set('severityBreathing', 'no')"
                            >
                                No
                            </x-ui.button>

                            <x-ui.button
                                type="button"
                                variant="outline"
                                color="zinc"
                                class="w-full justify-center"
                                @class([
                                    '!border-teal-500 !bg-teal-50 !text-teal-700' => $severityBreathing === 'leve',
                                ])
                                wire:click="$set('severityBreathing', 'leve')"
                            >
                                Leve
                            </x-ui.button>

                            <x-ui.button
                                type="button"
                                variant="outline"
                                color="zinc"
                                class="w-full justify-center"
                                @class([
                                    '!border-teal-500 !bg-teal-50 !text-teal-700' => $severityBreathing === 'grave',
                                ])
                                wire:click="$set('severityBreathing', 'grave')"
                            >
                                Grave
                            </x-ui.button>
                        </div>
                    </div>

                    <div>
                        <x-ui.text class="mb-2 text-base font-semibold">¿Hemorragia activa?</x-ui.text>
                        <div class="grid grid-cols-2 gap-3">
                            <x-ui.button
                                type="button"
                                variant="outline"
                                color="zinc"
                                class="w-full justify-center"
                                @class([
                                    '!border-teal-500 !bg-teal-50 !text-teal-700' => $severityBleeding === false,
                                ])
                                wire:click="$set('severityBleeding', false)"
                            >
                                No
                            </x-ui.button>

                            <x-ui.button
                                type="button"
                                variant="outline"
                                color="zinc"
                                class="w-full justify-center"
                                @class([
                                    '!border-teal-500 !bg-teal-50 !text-teal-700' => $severityBleeding === true,
                                ])
                                wire:click="$set('severityBleeding', true)"
                            >
                                Sí
                            </x-ui.button>
                        </div>
                    </div>

                    <div>
                        <x-ui.text class="mb-2 text-base font-semibold">¿Dolor torácico?</x-ui.text>
                        <div class="grid grid-cols-2 gap-3">
                            <x-ui.button
                                type="button"
                                variant="outline"
                                color="zinc"
                                class="w-full justify-center"
                                @class([
                                    '!border-teal-500 !bg-teal-50 !text-teal-700' => $severityChestPain === false,
                                ])
                                wire:click="$set('severityChestPain', false)"
                            >
                                No
                            </x-ui.button>

                            <x-ui.button
                                type="button"
                                variant="outline"
                                color="zinc"
                                class="w-full justify-center"
                                @class([
                                    '!border-teal-500 !bg-teal-50 !text-teal-700' => $severityChestPain === true,
                                ])
                                wire:click="$set('severityChestPain', true)"
                            >
                                Sí
                            </x-ui.button>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <x-ui.text class="text-base font-semibold">Escala de dolor</x-ui.text>
                            <x-ui.text class="font-semibold text-neutral-700">{{ $severityPainScale }} / 10</x-ui.text>
                        </div>

                        <x-ui.slider
                            wire:model.live="severityPainScale"
                            handleVariant="circle"
                            class="w-full"
                            tooltips
                            :fill-track="[true, false]"
                            x-init="$slider.formatTooltipUsing((value) => value.toFixed())"
                        />
                    </div>
                </div>
            </x-ui.card>

            <x-ui.field class="lg:col-span-2">
                <x-ui.label>Notas</x-ui.label>
                <x-ui.textarea wire:model.live="notes" placeholder="Escribe notas adicionales sobre el traslado" />
                <x-ui.error name="notes" />
            </x-ui.field>
        </div>

        <div class="flex justify-end gap-3 pt-2">
            <x-ui.button x-on:click="$data.close()" icon="x-mark" variant="outline" color="zinc">
                Cancelar
            </x-ui.button>

            <x-ui.button wire:click="save" icon="check" color="teal">
                Guardar
            </x-ui.button>
        </div>
    </div>
</x-ui.modal>
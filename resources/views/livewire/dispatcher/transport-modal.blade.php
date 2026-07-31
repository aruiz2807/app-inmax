<x-ui.modal
    id="transport-modal"
    animation="fade"
    width="5xl"
    heading="Nuevo traslado"
    description="Complete la información para registrar un nuevo traslado"
    x-on:close-transport-modal.window="$data.close()"
>
    <div class="space-y-6">
        <div class="space-y-4">
            <x-ui.card size="full">
                <x-ui.heading class="flex pb-2" level="h3" size="sm">
                    <x-ui.icon name="user" class="self-center" />
                    <x-ui.text class="text-base ml-2">Paciente</x-ui.text>
                </x-ui.heading>

                <x-ui.field>
                    <x-ui.label>Buscar por nombre o número de membresía</x-ui.label>
                    <x-ui.select
                        placeholder="Buscar paciente..."
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
                    <div class="mt-4 rounded-xl border border-neutral-200 bg-neutral-50 p-4">
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
                            <div>
                                <x-ui.text class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Edad</x-ui.text>
                                <x-ui.text class="mt-1 font-semibold">{{ $this->selectedPatient->age ?? 'N/D' }}</x-ui.text>
                            </div>
                            <div>
                                <x-ui.text class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Teléfono</x-ui.text>
                                <x-ui.text class="mt-1 font-semibold">{{ $this->selectedPatient->cleanPhone }}</x-ui.text>
                            </div>
                            <div>
                                <x-ui.text class="text-xs font-semibold uppercase tracking-wide text-neutral-500">Membresía</x-ui.text>
                                <x-ui.text class="mt-1 font-semibold">{{ $this->selectedPatient->policy?->number ?? 'Sin membresía' }}</x-ui.text>
                            </div>
                        </div>
                    </div>
                @endif
            </x-ui.card>

            <x-ui.card size="full">
                <x-ui.heading class="flex items-center mb-4" level="h3" size="sm">
                    <x-ui.text class="text-base ml-2">Datos operativos</x-ui.text>
                </x-ui.heading>

                <div x-data="{ openType: false, openSupplies: false, openSeverity: false }" class="space-y-4">

                    <x-ui.card size="full" class="border border-neutral-200">
                        <button type="button" class="w-full flex items-center justify-between" @click="openSeverity = !openSeverity">
                            <div class="flex items-center">
                                <x-ui.icon name="heart" class="self-center" />
                                <x-ui.text class="text-base ml-2">Evaluación severidad</x-ui.text>
                            </div>
                            <x-ui.icon name="chevron-down" class="transition-transform" x-bind:class="openSeverity ? 'rotate-180' : ''" />
                        </button>

                        <div x-show="openSeverity" x-collapse class="pt-4 space-y-5">
                            <div>
                                <div class="flex items-center justify-between mb-2">
                                    <x-ui.text class="text-base font-semibold">Evaluación de severidad</x-ui.text>
                                    <x-ui.badge variant="outline" color="{{ $this->severityColor ?? 'slate' }}" pill>{{ $this->severityLevel ?? 'Sin evaluar' }}</x-ui.badge>
                                </div>
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
                                    :min-value="0"
                                    :max-value="10"
                                    :step="1"
                                    :fill-track="[true, false]"
                                    x-init="$slider.formatTooltipUsing((value) => value.toFixed())"
                                />
                            </div>
                        </div>
                    </x-ui.card>

                    <x-ui.card size="full" class="border border-neutral-200">
                        <button type="button" class="w-full flex items-center justify-between" @click="openType = !openType">
                            <div class="flex items-center">
                                <x-ui.icon name="truck" class="self-center" />
                                <x-ui.text class="text-base ml-2">Tipo traslado</x-ui.text>
                            </div>
                            <x-ui.icon name="chevron-down" class="transition-transform" x-bind:class="openType ? 'rotate-180' : ''" />
                        </button>

                        <div x-show="openType" x-collapse class="pt-4">
                            <div class="space-y-3">
                                @forelse($this->transportServicesData as $serviceData)
                                    <label class="flex items-center justify-between gap-3 rounded-xl border border-neutral-200 bg-white p-3 cursor-pointer hover:border-teal-300 transition-colors">
                                        <div class="flex items-center gap-3">
                                            <input
                                                type="radio"
                                                name="selected-transport-service"
                                                wire:model.live="selectedTransportServiceId"
                                                value="{{ $serviceData['service']->id }}"
                                                class="h-4 w-4 border-neutral-300 text-teal-600 focus:ring-teal-500"
                                            />

                                            <div>
                                                <x-ui.text class="font-semibold text-neutral-900">{{ $serviceData['service']->name }}</x-ui.text>
                                                <x-ui.text class="text-xs text-neutral-500">${{ number_format((float) ($serviceData['service']->price ?? $this->resolveServicePrice((int) $serviceData['service']->id)), 2) }}</x-ui.text>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <x-ui.badge
                                                class="ml-2"
                                                :icon="$serviceData['included'] ? 'check-circle' : 'exclamation-triangle'"
                                                variant="outline"
                                                :color="$serviceData['included'] ? 'green' : 'yellow'"
                                                pill
                                            >
                                                {{ $serviceData['included'] ? 'Incluido' : 'Adicional' }}
                                            </x-ui.badge>
                                        </div>
                                    </label>
                                @empty
                                    <x-ui.text class="text-sm text-neutral-500">No hay servicios disponibles.</x-ui.text>
                                @endforelse

                                <x-ui.error name="selectedTransportServiceId" />

                                @if($this->isScheduledTransportService)
                                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 pt-2">
                                        <x-ui.field>
                                            <x-ui.label>Fecha programada</x-ui.label>
                                            <x-ui.input wire:model.live="scheduledDate" type="date" min="{{ now()->toDateString() }}" />
                                            <x-ui.error name="scheduledDate" />
                                        </x-ui.field>

                                        <x-ui.field>
                                            <x-ui.label>Hora programada</x-ui.label>
                                            <x-ui.input wire:model.live="scheduledTime" type="time" />
                                            <x-ui.error name="scheduledTime" />
                                        </x-ui.field>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </x-ui.card>

                    <x-ui.card size="full" class="border border-neutral-200">
                        <button type="button" class="w-full flex items-center justify-between" @click="openSupplies = !openSupplies">
                            <div class="flex items-center">
                                <x-ui.icon name="clipboard-document-list" class="self-center" />
                                <x-ui.text class="text-base ml-2">Insumos y adicionales</x-ui.text>
                            </div>
                            <x-ui.icon name="chevron-down" class="transition-transform" x-bind:class="openSupplies ? 'rotate-180' : ''" />
                        </button>

                        <div x-show="openSupplies" x-collapse class="pt-4 space-y-3">
                            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                                <label class="flex items-start justify-between gap-3 cursor-pointer">
                                    <div class="flex items-start gap-3">
                                        <input type="checkbox" wire:model.live="includeSupplies" class="mt-1 h-5 w-5 rounded border-neutral-300 text-teal-600 focus:ring-teal-500" />

                                        <div>
                                            <x-ui.text class="font-semibold text-base">Insumos adicionales</x-ui.text>
                                            <x-ui.text class="text-sm text-neutral-600">
                                                {{ count($selectedSupplyItems) }} insumos · ${{ number_format($this->selectedSupplyItemsSubtotal, 2) }}
                                            </x-ui.text>
                                        </div>
                                    </div>
                                </label>

                                @if($includeSupplies)
                                    <div class="mt-4 space-y-3 pl-8">
                                        @foreach($this->supplyCatalog as $key => $item)
                                            <label class="flex items-center justify-between gap-3 cursor-pointer">
                                                <div class="flex items-center gap-3">
                                                    <input type="checkbox" wire:model.live="selectedSupplyItems" value="{{ $key }}" class="h-5 w-5 rounded border-neutral-300 text-teal-600 focus:ring-teal-500" />
                                                    <x-ui.text class="font-semibold text-base">{{ $item['label'] }}</x-ui.text>
                                                </div>
                                                <x-ui.text class="font-semibold text-base text-neutral-700">${{ number_format($item['price'], 0) }}</x-ui.text>
                                            </label>
                                        @endforeach
                                    </div>
                                @endif

                                <x-ui.error name="selectedSupplyItems" />
                            </div>

                            <label class="flex items-start justify-between gap-3 rounded-2xl border border-neutral-200 bg-white p-4 cursor-pointer">
                                <div class="flex items-start gap-3">
                                    <input type="checkbox" wire:model.live="includeStairManeuvers" class="mt-1 h-5 w-5 rounded border-neutral-300 text-teal-600 focus:ring-teal-500" />
                                    <div>
                                        <x-ui.text class="font-semibold text-base">Maniobras de ascenso/descenso</x-ui.text>
                                        <x-ui.text class="text-sm text-neutral-600">A partir 2do piso sin elevador · $250</x-ui.text>
                                    </div>
                                </div>
                            </label>

                            <div class="rounded-2xl border border-neutral-200 bg-white p-4">
                                <label class="flex items-start justify-between gap-3 cursor-pointer">
                                    <div class="flex items-start gap-3">
                                        <input type="checkbox" wire:model.live="includeSceneWait" class="mt-1 h-5 w-5 rounded border-neutral-300 text-teal-600 focus:ring-teal-500" />
                                        <div>
                                            <x-ui.text class="font-semibold text-base">Tiempo de espera en escena</x-ui.text>
                                            <x-ui.text class="text-sm text-neutral-600">{{ $sceneWaitHours }} h · ${{ number_format($sceneWaitHours * 300, 0) }}</x-ui.text>
                                        </div>
                                    </div>
                                </label>

                                @if($includeSceneWait)
                                    <div class="mt-4 flex items-center justify-between pl-8">
                                        <x-ui.text class="text-base font-semibold text-[#1F3D5B]">Horas de espera</x-ui.text>

                                        <div class="flex items-center gap-3">
                                            <button type="button" wire:click="decrementSceneWaitHours" class="h-9 w-9 rounded-xl border border-neutral-200 bg-white text-lg font-bold text-[#1F3D5B]">-</button>
                                            <x-ui.text class="w-6 text-center text-xl font-semibold">{{ $sceneWaitHours }}</x-ui.text>
                                            <button type="button" wire:click="incrementSceneWaitHours" class="h-9 w-9 rounded-xl border border-neutral-200 bg-white text-lg font-bold text-[#1F3D5B]">+</button>
                                        </div>
                                    </div>
                                @endif

                                <x-ui.error name="sceneWaitHours" />
                            </div>
                        </div>
                    </x-ui.card>

                    <x-ui.card size="full" class="border border-neutral-200">
                        <x-ui.heading class="flex items-center justify-between" level="h3" size="sm">
                            <x-ui.text class="text-base">Presupuesto inicial</x-ui.text>
                        </x-ui.heading>
                        <div class="pt-2">
                            <x-ui.text class="text-sm text-neutral-500">{{ $this->selectedTransportService?->name ?? 'Traslado seleccionado' }} + adicionales</x-ui.text>
                            <x-ui.text class="text-2xl font-bold mt-1">{{ $this->formattedBudgetSubtotal }}</x-ui.text>
                        </div>
                    </x-ui.card>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <x-ui.field>
                            <x-ui.label>Origen</x-ui.label>
                            <x-ui.input wire:model.live="origin" placeholder="Escribe el origen del traslado" />
                            <x-ui.error name="origin" />
                        </x-ui.field>

                        <x-ui.field>
                            <x-ui.label>Destino</x-ui.label>
                            <x-ui.select wire:model.live="destinationSelection" placeholder="Buscar hospital o elegir otra dirección..." icon="building-office" searchable>
                                @foreach($this->hospitals as $hospital)
                                    <x-ui.select.option value="{{ $hospital->id }}">
                                        {{ $hospital->business_name }} - {{ $hospital->address }}
                                    </x-ui.select.option>
                                @endforeach

                                <x-ui.select.option value="custom">
                                    Otra dirección
                                </x-ui.select.option>
                            </x-ui.select>
                            <x-ui.error name="destinationSelection" />
                        </x-ui.field>

                        @if($destinationSelection === 'custom')
                            <x-ui.field class="md:col-span-2">
                                <x-ui.label>Dirección del destino</x-ui.label>
                                <x-ui.input wire:model.live="customDestinationAddress" placeholder="Escribe la dirección completa" />
                                <x-ui.error name="customDestinationAddress" />
                            </x-ui.field>
                        @endif
                    </div>

                    <x-ui.field>
                        <x-ui.label>Notas</x-ui.label>
                        <x-ui.textarea wire:model.live="notes" placeholder="Escribe notas adicionales sobre el traslado" />
                        <x-ui.error name="notes" />
                    </x-ui.field>
                </div>
            </x-ui.card>

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
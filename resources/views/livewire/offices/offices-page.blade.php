<div>
    <x-slot name="header">
        {{ __('app.offices') }}
    </x-slot>

    <div>
        <x-ui.card size="full">
            <x-ui.heading class="flex items-center justify-between mb-4" level="h3" size="sm">
                <span>Catalogo de consultorios</span>

                <x-ui.button color="teal" icon="plus-circle" wire:click="create">
                    Agregar consultorio
                </x-ui.button>
            </x-ui.heading>

            <p>Administre los consultorios</p>
        </x-ui.card>
    </div>

    <div class="pt-2">
        <x-ui.card size="full">
            <livewire:offices.offices-table />
        </x-ui.card>
    </div>

    <x-ui.modal
        id="office-modal"
        animation="fade"
        width="4xl"
        heading="{{$officeId ? 'Editar consultorio' : 'Nuevo consultorio'}}"
        description="Ingrese la siguiente información para registrar un consultorio"
        x-on:close-office-modal.window="$data.close()"
        x-on:open-office-modal.window="$data.open()"
    >
        <form wire:submit="save">
            <x-ui.fieldset label="Información del consultorio">
                 <x-ui.field required>
                    <x-ui.label>Nombre del consultorio</x-ui.label>
                    <x-ui.input wire:model="form.name" name="name" placeholder="Consultorio Angel Nuño" />
                    <x-ui.error name="form.name" />
                </x-ui.field>
            
                <x-ui.field required>
                    <x-ui.label>Dirección</x-ui.label>
                    <x-ui.textarea wire:model="form.address" name="address" />
                    <x-ui.error name="form.address" />
                </x-ui.field>

                <x-ui.field required>
                    <x-ui.label>Google Maps URL</x-ui.label>
                    <x-ui.textarea wire:model="form.maps_url" name="maps_url" />
                    <x-ui.error name="form.maps_url" />
                </x-ui.field>

            </x-ui.fieldset>

            <x-ui.fieldset class="mt-4" label="Asignación médica y horarios">
                <div class="grid gap-6 lg:grid-cols-2">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h4 class="text-sm font-semibold text-slate-800">Doctores asignados</h4>
                                <p class="mt-1 text-xs text-slate-500">Solo se muestran médicos INMAX.</p>
                            </div>
                            <span class="rounded-full bg-teal-100 px-2.5 py-1 text-xs font-medium text-teal-700">
                                {{ count($form->selectedDoctors) }} seleccionados
                            </span>
                        </div>

                        <div class="mt-4 space-y-3">
                            <div class="rounded-xl border border-slate-300 bg-white p-3">
                                <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Disponibles para agregar</p>

                                @if($availableDoctors->isEmpty())
                                    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-sm text-slate-500">
                                        No hay doctores disponibles con la especialidad requerida.
                                    </div>
                                @else
                                    @php
                                        $doctorsToPick = $availableDoctors->whereNotIn('id', $form->selectedDoctors ?? []);
                                    @endphp

                                    @if($doctorsToPick->isEmpty())
                                        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 px-4 py-5 text-sm text-slate-500">
                                            Ya seleccionaste todos los doctores disponibles.
                                        </div>
                                    @else
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($doctorsToPick as $doctor)
                                                <button
                                                    type="button"
                                                    wire:click="addDoctor({{ $doctor->id }})"
                                                    class="inline-flex items-center gap-2 rounded-full border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:border-teal-300 hover:bg-teal-50 hover:text-teal-700"
                                                >
                                                    <span>{{ $doctor->user?->name ?? 'Doctor #' . $doctor->id }}</span>
                                                    <span class="text-[10px] text-slate-500">Agregar</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                @endif
                            </div>

                            <div class="rounded-xl border border-teal-200 bg-teal-50/60 p-3">
                                <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-teal-700">Seleccionados</p>
                                @if(!empty($form->selectedDoctors))
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($availableDoctors->whereIn('id', $form->selectedDoctors) as $doctor)
                                            <button
                                                type="button"
                                                wire:click="removeDoctor({{ $doctor->id }})"
                                                class="inline-flex items-center gap-1 rounded-full border border-teal-300 bg-white px-2.5 py-1 text-xs font-medium text-teal-700 hover:bg-teal-100"
                                            >
                                                <span>{{ $doctor->user?->name ?? 'Doctor #' . $doctor->id }}</span>
                                                <span aria-hidden="true">x</span>
                                            </button>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-xs text-teal-700/80">Aun no has seleccionado doctores.</p>
                                @endif
                            </div>
                        </div>

                        <x-ui.error name="form.selectedDoctors" />
                    </div>

                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <h4 class="text-sm font-semibold text-slate-800">Horarios del consultorio</h4>
                                <p class="mt-1 text-xs text-slate-500">Agrega las horas en las que estará disponible este consultorio.</p>
                            </div>
                            <span class="inline-flex shrink-0 items-center whitespace-nowrap rounded-full bg-blue-100 px-2.5 py-1 text-xs font-medium leading-none text-blue-700">
                                {{ count($form->slots) }} slots
                            </span>
                        </div>

                        <div class="mt-4 space-y-3">
                            <div class="rounded-xl border border-slate-300 bg-white p-3">
                                <div class="mb-3 flex items-center justify-between gap-3">
                                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Horario</p>

                                    <div class="flex items-center gap-2">
                                        <x-ui.input wire:model="slotTime" type="time" class="min-w-36" />
                                        <x-ui.button
                                            type="button"
                                            wire:click="addSlotMarker"
                                            variant="outline"
                                            color="blue"
                                            icon="check"
                                            size="sm"
                                        />
                                    </div>
                                </div>

                                <p class="mb-3 text-xs text-slate-500">Agrega las horas que quieras considerar para este consultorio.</p>

                                <div class="rounded-xl border border-blue-200 bg-blue-50/60 p-3">
                                    <p class="mb-2 text-[11px] font-semibold uppercase tracking-wide text-blue-700">Horas seleccionadas</p>
                                    @if(!empty($slotMarkers))
                                        <div class="flex flex-wrap gap-2">
                                            @foreach($slotMarkers as $index => $marker)
                                                <button
                                                    type="button"
                                                    wire:click="removeSlotMarker({{ $index }})"
                                                    class="inline-flex items-center gap-2 rounded-full border border-blue-300 bg-white px-2.5 py-1 text-xs font-medium text-blue-700"
                                                >
                                                    <span>{{ $marker }}</span>
                                                    <span aria-hidden="true">x</span>
                                                </button>
                                            @endforeach
                                        </div>
                                    @else
                                        <p class="text-xs text-blue-700/80">Aún no has agregado horas.</p>
                                    @endif
                                </div>
                            </div>

                        </div>

                        <x-ui.error name="slotMarkers" />
                        <x-ui.error name="form.slots" />
                    </div>
                </div>
            </x-ui.fieldset>

            <div class="w-full flex justify-end gap-3 pt-4">
                <x-ui.button x-on:click="$data.close();" wire:click="resetForm" icon="x-mark" variant="outline">
                    Cancel
                </x-ui.button>

                <x-ui.button type="submit" icon="check" variant="primary" color="teal">
                    Guardar
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>

<div>
    <div class="relative w-full">
        <img src="/img/top.png" alt="Header" class="w-full object-cover">
    </div>

    <div class="grid grid-cols-[2rem_auto] justify-stretch items-center pt-4 pb-4">
        <x-ui.icon name="arrow-left" class="w-5 h-5 cursor-pointer" x-on:click="window.history.back()" />
        <x-ui.text class="text-2xl">Nota medica</x-ui.text>
    </div>

    <x-ui.card size="full" class="mx-auto">
        <x-ui.heading class="flex mb-4" level="h3" size="sm">
            <x-ui.icon name="calendar" class="self-center" />
            <x-ui.text class="text-lg ml-2">{{$appointment->formatted_date}}</x-ui.text>
        </x-ui.heading>

        <div class="flex mt-2">
            <x-ui.avatar size="lg" icon="user" color="teal" :src="$appointment->user->photo_url" circle />
            <div class="pl-4">
                <x-ui.text class="pt-1 text-lg">{{$appointment->user->name}}</x-ui.text>
                <x-ui.text class="text-sm opacity-75">{{$appointment->user->policy->number}}</x-ui.text>
            </div>
        </div>

        <div class="flex mt-2">
            <x-ui.avatar size="lg" icon="user" color="teal" :src="$user->photo_url" circle />
            <div class="pl-4">
                <x-ui.text class="pt-1 text-lg">{{$user->name}}</x-ui.text>
                <x-ui.text class="text-sm opacity-75">{{$user->doctor->specialty->name}}</x-ui.text>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card size="full" class="mx-auto mt-2">
        <x-ui.heading class="flex pb-2" level="h3" size="sm">
            <x-ui.icon name="clipboard-document-list" class="self-center" />
            <x-ui.text class="text-base ml-2">Servicios</x-ui.text>
        </x-ui.heading>

        <div class="flex flex-col w-full">
        @foreach($services as $service)
            <div class="grid grid-cols-12 items-center gap-2 pb-2">
                <div class="col-span-5">
                    <x-ui.text class="text-base pr-1">
                        {{ $service->service->name }}
                    </x-ui.text>
                </div>

                <div class="col-span-3 flex justify-center">
                    <x-ui.badge :icon="$service->covered_icon" variant="outline" :color="$service->covered_color" pill>
                        {{$service->covered_text}}
                    </x-ui.badge>
                </div>

                <div class="col-span-4 flex justify-end">
                    <x-ui.switch
                        wire:model.live="form.services.{{ $service->id }}"
                        label="Realizado"
                        onClass="bg-teal"
                        iconOff="x-mark"
                        iconOn="check"
                    />
                </div>
            </div>
        @endforeach

        @if(!collect($form->services ?? [])->contains(true))
            <span class="mt-2 text-sm text-red-500">
                Ningun servicio ha sido marcado como realizado.
            </span>
        @endif
        </div>
    </x-ui.card>

    @if($form->isDoctor)
    <x-ui.card size="full" class="mx-auto mt-2">
        <x-ui.heading class="flex pb-2" level="h3" size="sm">
            <x-ui.icon name="clipboard-document-list" class="self-center" />
            <x-ui.text class="text-base ml-2">Síntomas</x-ui.text>
        </x-ui.heading>

        <x-ui.textarea wire:model="form.symptoms" placeholder="Ingrese los sintomas que presenta el paciente..."/>
        <x-ui.error name="form.symptoms" />
    </x-ui.card>

    <x-ui.card size="full" class="mx-auto mt-2">
        <x-ui.heading class="flex pb-2" level="h3" size="sm">
            <x-ui.icon name="clipboard-document-list" class="self-center" />
            <x-ui.text class="text-base ml-2">Hallazgos físicos</x-ui.text>
        </x-ui.heading>

        <x-ui.textarea wire:model="form.findings" placeholder="Ingrese los hallazgos sobre el paciente"/>
        <x-ui.error name="form.findings" />
    </x-ui.card>

    <x-ui.card size="full" class="mx-auto mt-2">
        <x-ui.heading class="flex pb-2" level="h3" size="sm">
            <x-ui.icon name="clipboard-document-list" class="self-center" />
            <x-ui.text class="text-base ml-2">Diagnostico</x-ui.text>
        </x-ui.heading>

        <x-ui.textarea wire:model="form.diagnosis" placeholder="Ingrese el diagnostico sobre el paciente"/>
        <x-ui.error name="form.diagnosis" />
    </x-ui.card>

    <x-ui.card size="full" class="mx-auto mt-2">
        <x-ui.heading class="flex pb-2" level="h3" size="sm">
            <x-ui.icon name="clipboard-document-list" class="self-center" />
            <x-ui.text class="text-base ml-2">Tratamiento / Receta</x-ui.text>
        </x-ui.heading>

        <div class="flex flex-col gap-2">
            <x-ui.field>
                <x-ui.label>Medicamento</x-ui.label>
                <x-ui.select wire:model="medicationId" placeholder="Seleccione un medicamento" searchable>
                    @foreach($medications as $medication)
                        <x-ui.select.option value="{{ $medication->id }}">
                            {{ $medication->name }} ({{ $medication->trade_name }})
                        </x-ui.select.option>
                    @endforeach
                </x-ui.select>
                <x-ui.error name="medicationId" />
            </x-ui.field>

            <div class="grid grid-cols-2 gap-2">
                <x-ui.field>
                    <x-ui.label>Cantidad</x-ui.label>
                    <x-ui.input type="number" wire:model="quantity" />
                    <x-ui.error name="quantity" />
                </x-ui.field>
                <x-ui.field>
                    <x-ui.label>Dosis</x-ui.label>
                    <x-ui.input wire:model="dose" placeholder="Ej. 1 tableta" />
                    <x-ui.error name="dose" />
                </x-ui.field>
            </div>

            <div class="grid grid-cols-2 gap-2">
                <x-ui.field>
                    <x-ui.label>Frecuencia</x-ui.label>
                    <x-ui.input wire:model="frequency" placeholder="Ej. Cada 8 horas" />
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
                    @foreach($prescriptions as $prescription)
                        <div class="bg-gray-50 p-2 rounded-lg flex justify-between items-center shadow-sm border border-gray-100">
                            <div class="flex-1">
                                <x-ui.text class="font-bold text-sm">{{ $prescription->medication->name }}</x-ui.text>
                                <x-ui.text class="text-xs text-gray-600">
                                    {{ $prescription->quantity }} • {{ $prescription->dose }} • {{ $prescription->frequency }} • {{ $prescription->duration }}
                                </x-ui.text>
                            </div>
                            <x-ui.button wire:click="deletePrescription({{ $prescription->id }})" icon="trash" variant="danger" size="sm" class="ml-2" />
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </x-ui.card>
    @endif

    <x-ui.card size="full" class="mx-auto mt-2">
        <x-ui.heading class="flex pb-2" level="h3" size="sm">
            <x-ui.icon name="clipboard-document-list" class="self-center" />
            <x-ui.text class="text-base ml-2">Adjuntar archivo</x-ui.text>
        </x-ui.heading>

        <div class="flex flex-col w-full">
        @foreach($services as $service)
            @if(!empty($form->services[$service->id]))
            <div class="grid grid-cols-5 items-center gap-2 pb-2">
                <div class="col-span-2">
                    <x-ui.text class="text-base pr-2">{{$service->service->name}}</x-ui.text>
                </div>

                <div class="col-span-3">
                    <input type="file" wire:model="form.attachments.{{ $service->id }}" placeholder="Seleccione un archivo para adjuntar" class="pt-2 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200"/>
                </div>
            </div>

            <x-ui.error name="form.attachments.{{ $service->id }}" />

            <div class="w-full" wire:loading wire:target="form.attachments.{{ $service->id }}">
                <x-ui.text>Subiendo archivo...</x-ui.text>
            </div>
            @endif
        @endforeach
        </div>
    </x-ui.card>

    <x-ui.card size="full" class="mx-auto mt-2">
        <x-ui.heading class="flex pb-2" level="h3" size="sm">
            <x-ui.icon name="clipboard-document-list" class="self-center" />
            <x-ui.text class="text-base ml-2">Notas y recomendaciones</x-ui.text>
        </x-ui.heading>

        <x-ui.textarea wire:model="form.notes" placeholder="Ingrese las recomendaciones para el paciente"/>
        <x-ui.error name="form.notes" />
    </x-ui.card>

    <x-ui.card size="full" class="mx-auto mt-2">
        <x-ui.heading class="flex pb-2" level="h3" size="sm">
            <x-ui.icon name="banknotes" class="self-center" />
            <x-ui.text class="text-base ml-2">Cierre de cuenta</x-ui.text>
        </x-ui.heading>

        <x-ui.field>
            <x-ui.label>Monto total de la cuenta</x-ui.label>
            <x-ui.input
                wire:model.live="subtotal"
                name="subtotal" x-mask:dynamic="$money($input)"
                placeholder="0.00"
            >
                <x-slot name="prefix">$</x-slot>
            </x-ui.input>
        </x-ui.field>

        <x-ui.field class="mt-2">
            <x-ui.label>Cobro al paciente (Pago miembro)</x-ui.label>
            <x-ui.alerts variant="success" icon="currency-dollar">
                <x-ui.alerts.heading>{{$user_payment}}</x-ui.alerts.heading>
            </x-ui.alerts>
        </x-ui.field>

        <x-ui.field class="mt-2">
            <x-ui.label>Comision Inmax</x-ui.label>
            <x-ui.alerts variant="info" icon="currency-dollar">
                <x-ui.alerts.description>{{$commision}}</x-ui.alerts.description>
            </x-ui.alerts>
        </x-ui.field>

        <x-ui.field class="mt-2">
            <x-ui.label>Ganancia del proveedor</x-ui.label>
            <x-ui.alerts variant="info" icon="currency-dollar">
                <x-ui.alerts.description>{{$total}}</x-ui.alerts.description>
            </x-ui.alerts>
        </x-ui.field>
    </x-ui.card>

    <div class="flex justify-center mt-4">
        <x-ui.button class="w-40 mr-1" wire:click="save" variant="outline" color="blue" icon="clipboard"
            :disabled="!collect($form->services ?? [])->contains(true)"
        >
            Guardar
        </x-ui.button>
    </div>

     <x-ui.modal
        id="notes-modal"
        animation="fade"
        width="md"
        heading="Finalizar consulta"
        description="Desea finalizar la consulta? Si acepta se descontará la consulta del cliente y se generara la receta digital"
        x-on:open-notes-modal.window="$data.open()"
        x-on:close-notes-modal.window="$data.close()"
    >
        <div class="flex justify-end gap-3 pt-4">
            <x-ui.button x-on:click="$data.close()" icon="x-mark" variant="outline">
                Cancelar
            </x-ui.button>

            <x-ui.button color="teal" icon="check" wire:click="confirmNotes">
                Confirmar
            </x-ui.button>
        </div>
    </x-ui.modal>
</div>

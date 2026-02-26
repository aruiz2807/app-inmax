<div class="max-w-md mx-auto bg-white min-h-screen overflow-hidden font-sans">
    <div class="relative w-full">
        <img src="/img/top.png" alt="Header" class="w-full object-cover">
    </div>

    <div class="grid grid-cols-[2rem_auto] justify-stretch items-center pt-4 pb-4">
        <x-ui.icon name="arrow-left" class="w-5 h-5 cursor-pointer" x-on:click="window.history.back()" />
        <x-ui.text class="text-2xl">Nota medica</x-ui.text>
    </div>

    <x-ui.card size="full" class="mx-auto">
        <x-ui.heading class="flex" level="h3" size="sm">
            <x-ui.icon name="calendar" class="self-center" />
            <x-ui.text class="text-lg ml-2">Consulta</x-ui.text>
        </x-ui.heading>

        <div class="grid grid-cols-[auto_6rem] justify-stretch items-center pt-2">
            <x-ui.text class="text-base">{{$appointment->formatted_date}}</x-ui.text>
            <x-ui.badge :icon="$appointment->covered_icon" variant="outline" :color="$appointment->covered_color" pill>
                {{$appointment->covered_text}}
            </x-ui.badge>
        </div>

        <div class="flex mt-2">
            <x-ui.avatar size="lg" icon="user" color="teal" src="/img/user.png" circle />
            <div class="pl-4">
                <x-ui.text class="pt-1 text-lg">{{$appointment->user->name}}</x-ui.text>
                <x-ui.text class="text-sm opacity-75">{{$appointment->user->policy->number}}</x-ui.text>
            </div>
        </div>

        <div class="flex mt-2">
            <x-ui.avatar size="lg" icon="user" color="teal" src="/img/doctor.png" circle />
            <div class="pl-4">
                <x-ui.text class="pt-1 text-lg">{{$appointment->doctor->user->name}}</x-ui.text>
                <x-ui.text class="text-sm opacity-75">{{$appointment->doctor->specialty->name}}</x-ui.text>
            </div>
        </div>
    </x-ui.card>

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
            <x-ui.text class="text-base ml-2">Tratamiento</x-ui.text>
        </x-ui.heading>

        <x-ui.textarea wire:model="form.treatment" placeholder="Ingrese los medicamentos para el paciente"/>
        <x-ui.error name="form.treatment" />
    </x-ui.card>

    <x-ui.card size="full" class="mx-auto mt-2">
        <x-ui.heading class="flex pb-2" level="h3" size="sm">
            <x-ui.icon name="clipboard-document-list" class="self-center" />
            <x-ui.text class="text-base ml-2">Adjuntar archivo</x-ui.text>
        </x-ui.heading>

        <input type="file" wire:model="form.attachment" placeholder="Seleccione un archivo para adjuntar" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200"/>
        <x-ui.error name="form.attachment" />
        <div wire:loading wire:target="form.attachment">
            Subiendo archivo...
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

    <div class="flex justify-center mt-4">
        <x-ui.button class="w-40 mr-1" wire:click="save" variant="outline" color="blue" icon="clipboard">
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

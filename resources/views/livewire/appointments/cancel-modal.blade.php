<x-ui.modal
    id="cancel-appointment-modal"
    animation="fade"
    width="2xl"
    heading="Cancelar cita"
    description="Está seguro que desea cancelar esta cita?"
    x-on:open-cancel-appointment-modal.window="$data.open()"
    x-on:close-cancel-appointment-modal.window="$data.close()"
>
    <x-ui.text class="pl-10 m-2 font-semibold text-base">
        Asegurado: {{$appointment?->user->name}}
    </x-ui.text>

    <x-ui.text class="pl-10 m-2 font-semibold text-base">
        Medico: {{$appointment?->doctor->user->name}}
    </x-ui.text>

    <x-ui.text class="pl-10 m-2 font-semibold text-base">
        Motivo: {{$appointment?->doctor->specialty->service->name}}
    </x-ui.text>

    <x-ui.text class="pl-10 m-2 font-semibold text-base">
        Fecha: {{$appointment?->date->format('d/m/Y')}} {{$appointment?->time->format('H:i A')}}
    </x-ui.text>

    <div class="flex justify-end gap-3 pt-4">
        <x-ui.button x-on:click="$data.close()" icon="x-mark" variant="outline">
            Cancelar
        </x-ui.button>

        <x-ui.button color="teal" icon="check" wire:click="confirmCancel">
            Confirmar
        </x-ui.button>
    </div>
</x-ui.modal>

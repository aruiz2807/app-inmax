<x-ui.modal
    id="cancel-modal"
    animation="fade"
    width="2xl"
    heading="Cancelar póliza"
    description="Está seguro que desea cancelar esta póliza?"
    x-on:open-cancel-modal.window="$data.open()"
    x-on:close-cancel-modal.window="$data.close()"
>
    <x-ui.text class="pl-10 m-2 font-semibold text-base">
        Número de poliza: {{$policy_number}}
    </x-ui.text>

    <x-ui.text class="pl-10 m-2 font-semibold text-base">
        Propietario: {{$policy_user_name}}
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

<x-ui.modal
    id="noshow-modal"
    animation="fade"
    width="md"
    heading="Finalizar cita"
    description="El cliente no se presento a la cita? Si confirma, se marcara como no asistio."
    x-on:open-noshow-modal.window="$data.open()"
    x-on:close-noshow-modal.window="$data.close()"
>
    <div class="flex justify-end gap-3 pt-4">
        <x-ui.button x-on:click="$data.close()" icon="x-mark" variant="outline">
            Cancelar
        </x-ui.button>

        <x-ui.button color="teal" icon="check" wire:click="confirmNoshow">
            Confirmar
        </x-ui.button>
    </div>
</x-ui.modal>

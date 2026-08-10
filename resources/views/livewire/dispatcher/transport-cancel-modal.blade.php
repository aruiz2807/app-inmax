<x-ui.modal
    id="transport-cancel-modal"
    animation="fade"
    width="2xl"
    heading="Cancelar traslado"
    description="Indica el motivo de la cancelación para registrar el comentario del traslado."
    x-on:open-transport-cancel-modal.window="$data.open()"
    x-on:close-transport-cancel-modal.window="$data.close()"
>
    @if($cancellingAppointment)
        <div class="space-y-4">
            <x-ui.text class="text-sm text-neutral-600">
                Se cancelará el traslado para <span class="font-semibold">{{ $cancellingAppointment->user?->name ?? 'el paciente' }}</span>.
            </x-ui.text>

            <x-ui.field>
                <x-ui.label>Motivo de la cancelación</x-ui.label>
                <x-ui.textarea
                    wire:model="cancelReason"
                    placeholder="Escribe el motivo de la cancelación"
                    rows="4"
                />
                <x-ui.error name="cancelReason" />
            </x-ui.field>

            <div class="flex justify-end gap-3 pt-2">
                <x-ui.button x-on:click="$wire.resetCancelState(); $data.close()" icon="x-mark" variant="outline" color="zinc">
                    Cancelar
                </x-ui.button>

                <x-ui.button wire:click="confirmCancelTransport" icon="check" color="red">
                    Confirmar cancelación
                </x-ui.button>
            </div>
        </div>
    @endif
</x-ui.modal>

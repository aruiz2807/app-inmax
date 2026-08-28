<x-ui.modal
    id="history-appointment-modal"
    animation="fade"
    :width="($isMobileDevice ?? false) ? 'screen' : '5xl'"
    heading="Historial clinico completo"
    description="Consultas, notas medicas, recetas y archivos de resultados del miembro"
    x-on:open-history-appointment-modal.window="$data.open()"
    x-on:close-history-appointment-modal.window="$data.close()"
>
    @if($historyPatient)
        <div @class(['space-y-4 overflow-y-auto', 'max-h-[80vh] pr-1' => ! ($isMobileDevice ?? false), 'h-[calc(100vh-6.5rem)] px-1 pb-4' => ($isMobileDevice ?? false)])>
            @include('livewire.appointments.partials.history-content')

            <div class="flex justify-end pt-2">
                <x-ui.button x-on:click="$data.close()" icon="x-mark" variant="outline">
                    Cerrar
                </x-ui.button>
            </div>
        </div>
    @endif
</x-ui.modal>

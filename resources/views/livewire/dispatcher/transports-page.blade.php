<div>
    <x-slot name="header">
        Traslados
    </x-slot>

    <x-ui.card size="full">
        <x-ui.heading class="flex items-center justify-between mb-4" level="h3" size="sm">
            <span>Traslados</span>

            <x-ui.modal.trigger id="doctor-modal" wire:click="resetForm">
                    <x-ui.button color="teal" icon="plus-circle">
                        Nuevo traslado
                    </x-ui.button>
                </x-ui.modal.trigger>
        </x-ui.heading>

        <p>Gestione y de seguimiento a los traslados asignados a cabina.</p>
    </x-ui.card>

    <div class="pt-2">
        <x-ui.card size="full">
            <div class="flex gap-2 mb-4 border-b border-neutral-200">
                <button
                    type="button"
                    wire:click="setTab('booked')"
                    @class([
                        'px-4 py-2 text-sm font-medium transition-colors',
                        'border-b-2 border-teal-600 text-teal-600' => $tab === 'booked',
                        'text-neutral-600 hover:text-neutral-900' => $tab !== 'booked',
                    ])
                >
                    Programado
                </button>

                <button
                    type="button"
                    wire:click="setTab('completed')"
                    @class([
                        'px-4 py-2 text-sm font-medium transition-colors',
                        'border-b-2 border-teal-600 text-teal-600' => $tab === 'completed',
                        'text-neutral-600 hover:text-neutral-900' => $tab !== 'completed',
                    ])
                >
                    Finalizado
                </button>

                <button
                    type="button"
                    wire:click="setTab('cancelled')"
                    @class([
                        'px-4 py-2 text-sm font-medium transition-colors',
                        'border-b-2 border-teal-600 text-teal-600' => $tab === 'cancelled',
                        'text-neutral-600 hover:text-neutral-900' => $tab !== 'cancelled',
                    ])
                >
                    Cancelado
                </button>

                <button
                    type="button"
                    wire:click="setTab('all')"
                    @class([
                        'px-4 py-2 text-sm font-medium transition-colors',
                        'border-b-2 border-teal-600 text-teal-600' => $tab === 'all',
                        'text-neutral-600 hover:text-neutral-900' => $tab !== 'all',
                    ])
                >
                    Todos
                </button>
            </div>

            <div class="rounded-lg border border-dashed border-neutral-300 bg-neutral-50 px-4 py-10 text-center">
                <p class="text-sm font-medium text-neutral-700">No hay traslados para mostrar en este estatus.</p>
                <p class="mt-1 text-xs text-neutral-500">Cuando registres movimientos, apareceran aqui automaticamente.</p>
            </div>
        </x-ui.card>
    </div>
</div>
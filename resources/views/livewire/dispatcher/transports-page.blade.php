<div>
    <x-slot name="header">
        Traslados
    </x-slot>

    <x-ui.card size="full">
        <x-ui.heading class="flex items-center justify-between mb-4" level="h3" size="sm">
            <span>Traslados</span>

            <x-ui.modal.trigger id="transport-modal" wire:click="startCreate">
                    <x-ui.button color="teal" icon="plus-circle">
                        Nuevo servicio
                    </x-ui.button>
                </x-ui.modal.trigger>
        </x-ui.heading>

        <p>Gestione y de seguimiento a los servicios asignados a cabina.</p>
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
                    wire:click="setTab('in_progress')"
                    @class([
                        'px-4 py-2 text-sm font-medium transition-colors',
                        'border-b-2 border-teal-600 text-teal-600' => $tab === 'in_progress',
                        'text-neutral-600 hover:text-neutral-900' => $tab !== 'in_progress',
                    ])
                >
                    En progreso
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

            <livewire:dispatcher.transports-table :tab="$tab" :key="'dispatcher-transports-table-'.$tab" />
        </x-ui.card>
    </div>

    @includeIf('livewire.dispatcher.transport-modal')
    @includeIf('livewire.dispatcher.transport-detail-modal')
    @includeIf('livewire.dispatcher.transport-close-modal')
    @includeIf('livewire.dispatcher.transport-cancel-modal')
</div>
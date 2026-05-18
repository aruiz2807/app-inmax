<div
    x-data
    x-on:open-receptionist-appointment-detail.window="$wire.openDetails($event.detail.appointmentId)"
>
    <x-slot name="header">
        Check-OUT
    </x-slot>

    <x-ui.card size="full">
        <x-ui.heading class="flex items-center justify-between mb-4" level="h3" size="sm">
            <span>Consultas</span>
        </x-ui.heading>

        <p>Liquida consultas a medida que las pacientes terminan. La cola se actualiza en tiempo real.</p>
    </x-ui.card>

    <div id="payment-section" class="pt-2">
        <x-ui.card size="full">
            <div class="flex gap-2 mb-4 border-b border-neutral-200">
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

                <button
                    type="button"
                    wire:click="setTab('pending')"
                    @class([
                        'px-4 py-2 text-sm font-medium transition-colors',
                        'border-b-2 border-teal-600 text-teal-600' => $tab === 'pending',
                        'text-neutral-600 hover:text-neutral-900' => $tab !== 'pending',
                    ])
                >
                    Pendientes
                </button>

                <button
                    type="button"
                    wire:click="setTab('paid')"
                    @class([
                        'px-4 py-2 text-sm font-medium transition-colors',
                        'border-b-2 border-teal-600 text-teal-600' => $tab === 'paid',
                        'text-neutral-600 hover:text-neutral-900' => $tab !== 'paid',
                    ])
                >
                    Pagados
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
                    Canceladas
                </button>
            </div>

            <livewire:receptionist.appointments-table :tab="$tab" :key="'receptionist-appointments-table-'.$tab" />
        </x-ui.card>
    </div>

    @include('livewire.receptionist.appointment-details-modal')
</div>

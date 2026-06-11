<div
    x-data
    x-on:accept-receptionist-request.window="$wire.acceptRequest($event.detail.appointmentId)"
    x-on:reject-receptionist-request.window="$wire.rejectRequest($event.detail.appointmentId)"
    x-on:show-receptionist-request-detail.window="$wire.openDetails($event.detail.appointmentId)"
>
    <x-slot name="header">
        Solicitudes
    </x-slot>

    <x-ui.card size="full">
        <x-ui.heading class="flex items-center justify-between mb-4" level="h3" size="sm">
            <span>Solicitudes</span>
        </x-ui.heading>

        <p>Revise y autorice las consultas solicitadas por los miembros.</p>
    </x-ui.card>

    <div class="grid grid-cols-1 gap-4 pt-2 md:grid-cols-3">
        <x-ui.card size="full" class="border-t-2 border-yellow-400">
            <p class="text-xs font-semibold tracking-wide uppercase text-neutral-500">Solicitudes pendientes</p>
            <p class="mt-2 text-3xl font-bold text-neutral-900">{{ $this->pendingCount }}</p>
            <p class="text-xs text-neutral-500">Por revisar</p>
        </x-ui.card>

        <x-ui.card size="full" class="border-t-2 border-teal-500">
            <p class="text-xs font-semibold tracking-wide uppercase text-neutral-500">Solicitudes aceptadas</p>
            <p class="mt-2 text-3xl font-bold text-neutral-900">{{ $this->bookedCount }}</p>
            <p class="text-xs text-neutral-500">Aprobadas y en proceso</p>
        </x-ui.card>

        <x-ui.card size="full" class="border-t-2 border-red-500">
            <p class="text-xs font-semibold tracking-wide uppercase text-neutral-500">Solicitudes rechazadas</p>
            <p class="mt-2 text-3xl font-bold text-neutral-900">{{ $this->rejectedCount }}</p>
            <p class="text-xs text-neutral-500">Rechazadas</p>
        </x-ui.card>
    </div>

    <div class="pt-2">
        <x-ui.card size="full">
            <div class="flex gap-2 mb-4 border-b border-neutral-200">
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
                    wire:click="setTab('booked')"
                    @class([
                        'px-4 py-2 text-sm font-medium transition-colors',
                        'border-b-2 border-teal-600 text-teal-600' => $tab === 'booked',
                        'text-neutral-600 hover:text-neutral-900' => $tab !== 'booked',
                    ])
                >
                    Aceptadas
                </button>

                <button
                    type="button"
                    wire:click="setTab('rejected')"
                    @class([
                        'px-4 py-2 text-sm font-medium transition-colors',
                        'border-b-2 border-teal-600 text-teal-600' => $tab === 'rejected',
                        'text-neutral-600 hover:text-neutral-900' => $tab !== 'rejected',
                    ])
                >
                    Rechazadas
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
                    Todas
                </button>
            </div>

            <livewire:receptionist.requests-table :tab="$tab" :key="'receptionist-requests-table-'.$tab" />
        </x-ui.card>
    </div>

    @include('livewire.receptionist.request-details-modal')
</div>

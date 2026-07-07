<div class="space-y-4">
    <x-slot name="header">
        Consultas
    </x-slot>

    <x-ui.card size="full">
        <x-ui.heading class="flex items-center justify-between mb-2" level="h3" size="sm">
            <span>Consultas</span>
        </x-ui.heading>
        <p class="text-sm text-neutral-600">Vista de escritorio para seguimiento de consultas con búsqueda, tabs y tabla unificada.</p>
    </x-ui.card>

    <div class="grid grid-cols-1 gap-4 pt-2 md:grid-cols-3">
        <x-ui.card size="full" class="border-t-2 border-yellow-400">
            <p class="text-xs font-semibold tracking-wide uppercase text-neutral-500">Próximas</p>
            <p class="mt-2 text-3xl font-bold text-neutral-900">{{ $this->upcomingCount }}</p>
            <p class="text-xs text-neutral-500">Por atender</p>
        </x-ui.card>

        <x-ui.card size="full" class="border-t-2 border-green-500">
            <p class="text-xs font-semibold tracking-wide uppercase text-neutral-500">Completadas</p>
            <p class="mt-2 text-3xl font-bold text-neutral-900">{{ $this->completedCount }}</p>
            <p class="text-xs text-neutral-500">Finalizadas</p>
        </x-ui.card>

        <x-ui.card size="full" class="border-t-2 border-red-500">
            <p class="text-xs font-semibold tracking-wide uppercase text-neutral-500">Canceladas</p>
            <p class="mt-2 text-3xl font-bold text-neutral-900">{{ $this->cancelledCount }}</p>
            <p class="text-xs text-neutral-500">Canceladas / No asistio</p>
        </x-ui.card>
    </div>

    <div id="appointments-section" class="pt-2">
        <x-ui.card size="full">
            <div class="flex gap-2 mb-4 border-b border-neutral-200">
                <button
                    type="button"
                    wire:click="setTab('upcoming')"
                    @class([
                        'px-4 py-2 text-sm font-medium transition-colors',
                        'border-b-2 border-teal-600 text-teal-600' => $tab === 'upcoming',
                        'text-neutral-600 hover:text-neutral-900' => $tab !== 'upcoming',
                    ])
                >
                    Próximas
                </button>

                <button
                    type="button"
                    wire:click="setTab('past')"
                    @class([
                        'px-4 py-2 text-sm font-medium transition-colors',
                        'border-b-2 border-teal-600 text-teal-600' => $tab === 'past',
                        'text-neutral-600 hover:text-neutral-900' => $tab !== 'past',
                    ])
                >
                    Pasadas
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

            <livewire:doctor.appointments-table :tab="$tab" :key="'doctor-appointments-table-'.$tab" />
        </x-ui.card>
    </div>

    @include('livewire.doctor.appointments-details')
</div>

<div class="flex flex-col gap-3 pb-3 border-b border-neutral-100 mb-3 lg:flex-row lg:items-end lg:justify-between">
    <div class="flex flex-wrap items-end gap-2">
        @include('livewire.doctor.appointments-date-presets')
    </div>

    <div class="flex items-center gap-2">
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
    </div>
</div>

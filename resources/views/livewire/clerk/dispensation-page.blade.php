<div>
    <x-slot name="header">
        Surtir
    </x-slot>

    <x-ui.card size="full">
        <x-ui.heading class="flex items-center justify-between mb-4" level="h3" size="sm">
            <span>Surtir recetas</span>
        </x-ui.heading>

        <p>Surte las recetas. La cola se actualiza en tiempo real.</p>
    </x-ui.card>

    <div class="grid grid-cols-1 gap-4 pt-2 md:grid-cols-3">
        <x-ui.card size="full" class="border-t-2 border-yellow-400">
            <p class="text-xs font-semibold tracking-wide uppercase text-neutral-500">Pendientes de surtir</p>
            <p class="mt-2 text-3xl font-bold text-neutral-900">{{ $this->pendingCount }}</p>
            <p class="text-xs text-neutral-500">Recetas por atender</p>
        </x-ui.card>

        <x-ui.card size="full" class="border-t-2 border-blue-500">
            <p class="text-xs font-semibold tracking-wide uppercase text-neutral-500">Parcialmente surtidas</p>
            <p class="mt-2 text-3xl font-bold text-neutral-900">{{ $this->partialCount }}</p>
            <p class="text-xs text-neutral-500">Con entrega incompleta</p>
        </x-ui.card>

        <x-ui.card size="full" class="border-t-2 border-green-500">
            <p class="text-xs font-semibold tracking-wide uppercase text-neutral-500">Surtidas</p>
            <p class="mt-2 text-3xl font-bold text-neutral-900">{{ $this->filledCount }}</p>
            <p class="text-xs text-neutral-500">Entregadas por completo</p>
        </x-ui.card>
    </div>

    <div class="pt-2">
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
                    wire:click="setTab('partial')"
                    @class([
                        'px-4 py-2 text-sm font-medium transition-colors',
                        'border-b-2 border-teal-600 text-teal-600' => $tab === 'partial',
                        'text-neutral-600 hover:text-neutral-900' => $tab !== 'partial',
                    ])
                >
                    Parciales
                </button>

                <button
                    type="button"
                    wire:click="setTab('filled')"
                    @class([
                        'px-4 py-2 text-sm font-medium transition-colors',
                        'border-b-2 border-teal-600 text-teal-600' => $tab === 'filled',
                        'text-neutral-600 hover:text-neutral-900' => $tab !== 'filled',
                    ])
                >
                    Surtidas
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
                    Vencidas
                </button>
            </div>

            <livewire:clerk.dispensation-table :tab="$tab" :key="'clerk-dispensation-table-'.$tab" />
        </x-ui.card>
    </div>

    <livewire:clerk.checkout-modal />
</div>

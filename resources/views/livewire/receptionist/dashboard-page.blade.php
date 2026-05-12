<div>
    <x-slot name="header">
        Home
    </x-slot>

    <x-ui.card size="full">
        <x-ui.heading class="flex items-center justify-between mb-4" level="h3" size="sm">
            <span>Consultas</span>
        </x-ui.heading>

        <p>Listado de consultas para recepcion.</p>
    </x-ui.card>

    <div id="payment-section" class="pt-2">
        <x-ui.card size="full">
            @php
                $activeTab = request()->query('tab', 'all');
            @endphp

            <div class="flex gap-2 mb-4 border-b border-neutral-200">
                <a
                    href="{{ route('receptionist.dashboard', ['tab' => 'all']) }}"
                    @class([
                        'px-4 py-2 text-sm font-medium transition-colors',
                        'border-b-2 border-teal-600 text-teal-600' => $activeTab === 'all',
                        'text-neutral-600 hover:text-neutral-900' => $activeTab !== 'all',
                    ])
                >
                    Todos
                </a>

                <a
                    href="{{ route('receptionist.dashboard', ['tab' => 'pending']) }}"
                    @class([
                        'px-4 py-2 text-sm font-medium transition-colors',
                        'border-b-2 border-teal-600 text-teal-600' => $activeTab === 'pending',
                        'text-neutral-600 hover:text-neutral-900' => $activeTab !== 'pending',
                    ])
                >
                    Pendientes
                </a>

                <a
                    href="{{ route('receptionist.dashboard', ['tab' => 'paid']) }}"
                    @class([
                        'px-4 py-2 text-sm font-medium transition-colors',
                        'border-b-2 border-teal-600 text-teal-600' => $activeTab === 'paid',
                        'text-neutral-600 hover:text-neutral-900' => $activeTab !== 'paid',
                    ])
                >
                    Pagados
                </a>
            </div>

            <livewire:receptionist.appointments-table />
        </x-ui.card>
    </div>
</div>

<div>
    <!-- Tabs -->
    <div class="flex gap-2 mb-4 border-b border-neutral-200">
        <button
            wire:click="$dispatch('tab-changed', { tab: 'all' })"
            @class([
                'px-4 py-2 text-sm font-medium transition-colors',
                'border-b-2 border-teal-600 text-teal-600' => $tab === 'all',
                'text-neutral-600 hover:text-neutral-900' => $tab !== 'all',
            ])
        >
            Todos
        </button>

        <button
            wire:click="$dispatch('tab-changed', { tab: 'pending' })"
            @class([
                'px-4 py-2 text-sm font-medium transition-colors',
                'border-b-2 border-teal-600 text-teal-600' => $tab === 'pending',
                'text-neutral-600 hover:text-neutral-900' => $tab !== 'pending',
            ])
        >
            Pendientes
        </button>

        <button
            wire:click="$dispatch('tab-changed', { tab: 'paid' })"
            @class([
                'px-4 py-2 text-sm font-medium transition-colors',
                'border-b-2 border-teal-600 text-teal-600' => $tab === 'paid',
                'text-neutral-600 hover:text-neutral-900' => $tab !== 'paid',
            ])
        >
            Pagados
        </button>
    </div>

    <!-- Table -->
    <div class="overflow-x-auto">
        <table class="min-w-full border-collapse border border-neutral-200 rounded-lg overflow-hidden">
            <thead class="bg-neutral-100">
                <tr>
                    <th class="border border-neutral-200 px-4 py-2 text-left text-sm font-semibold">Fecha</th>
                    <th class="border border-neutral-200 px-4 py-2 text-left text-sm font-semibold">Hora</th>
                    <th class="border border-neutral-200 px-4 py-2 text-left text-sm font-semibold">Paciente</th>
                    <th class="border border-neutral-200 px-4 py-2 text-left text-sm font-semibold">Médico</th>
                    <th class="border border-neutral-200 px-4 py-2 text-left text-sm font-semibold">Estado</th>
                    <th class="border border-neutral-200 px-4 py-2 text-left text-sm font-semibold">Pago</th>
                    <th class="border border-neutral-200 px-4 py-2 text-left text-sm font-semibold">Monto</th>
                </tr>
            </thead>
            <tbody>
                @forelse($appointments as $appointment)
                    <tr class="hover:bg-neutral-50">
                        <td class="border border-neutral-200 px-4 py-2 text-sm">
                            {{ $appointment->date->format('d/m/Y') }}
                        </td>
                        <td class="border border-neutral-200 px-4 py-2 text-sm">
                            {{ $appointment->time->format('H:i') }}
                        </td>
                        <td class="border border-neutral-200 px-4 py-2 text-sm">
                            {{ $appointment->user?->name ?? 'N/A' }}
                        </td>
                        <td class="border border-neutral-200 px-4 py-2 text-sm">
                            {{ $appointment->doctor?->user?->name ?? 'N/A' }}
                        </td>
                        <td class="border border-neutral-200 px-4 py-2 text-sm">
                            <x-ui.badge
                                :icon="$appointment->status_icon"
                                :color="$appointment->status_color"
                                variant="outline"
                                pill
                            >
                                {{ $appointment->formatted_status }}
                            </x-ui.badge>
                        </td>
                        <td class="border border-neutral-200 px-4 py-2 text-sm">
                            @if($appointment->user_payment > 0)
                                <span class="inline-block px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-semibold">
                                    Pagado
                                </span>
                            @else
                                <span class="inline-block px-2 py-1 bg-yellow-100 text-yellow-800 rounded text-xs font-semibold">
                                    Pendiente
                                </span>
                            @endif
                        </td>
                        <td class="border border-neutral-200 px-4 py-2 text-sm font-semibold">
                            ${{ number_format($appointment->user_payment, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="border border-neutral-200 px-4 py-4 text-center text-neutral-600">
                            No hay citas para este filtro
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-4">
        {{ $appointments->links() }}
    </div>
</div>

<x-ui.card size="full">
    <x-application-logo class="block h-12 w-auto" />

    <x-ui.text class="mt-8 text-2xl font-semibold text-neutral-900">
        Hola {{ auth()->user()->name }}!
    </x-ui.text>

    <x-ui.text class="mt-2 text-lg mb-8">
        Bienvenido a la consola de administración de INMAX
    </x-ui.text>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-12">
        <div class="p-6 bg-white border border-neutral-200 rounded-xl shadow-sm">
            <livewire:home.charts.policies-by-month-chart />
        </div>
        <div class="p-6 bg-white border border-neutral-200 rounded-xl shadow-sm">
            <livewire:home.charts.policies-by-seller-chart />
        </div>
    </div>

    <x-ui.card size="full" class="mt-6">
        <x-ui.heading size="sm" class="mb-4">Citas agendadas de los próximos 7 días</x-ui.heading>

        <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900 shadow-sm">
            <table class="w-full text-left text-sm">
                <thead class="bg-neutral-50 text-neutral-600 dark:bg-neutral-950 dark:text-neutral-400">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Id</th>
                        <th class="px-4 py-3 font-semibold">Miembro</th>
                        <th class="px-4 py-3 font-semibold">Médico/Consultorio</th>
                        <th class="px-4 py-3 font-semibold">Fecha</th>
                        <th class="px-4 py-3 font-semibold">Hora</th>
                        <th class="px-4 py-3 font-semibold">Estatus</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-200 dark:divide-neutral-800">
                    @forelse($upcomingAppointments as $appointment)
                        <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-950/50 transition-colors">
                            <td class="px-4 py-3">{{ $appointment->id }}</td>
                            <td class="px-4 py-3 font-medium">{{ $appointment->user?->name }}</td>
                            <td class="px-4 py-3">{{ $appointment->doctor?->user?->name ?? $appointment->office?->name }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $appointment->date?->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $appointment->time?->format('H:i A') }}</td>
                            <td class="px-4 py-3"><x-status-badge :status="$appointment->status" /></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-6 text-center text-neutral-500">
                                No hay citas agendadas para los próximos 7 días.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.card size="full" class="mt-6">
        <x-ui.heading size="sm" class="mb-4">Resumen General</x-ui.heading>

        <div class="flex flex-wrap justify-center gap-6 lg:justify-between text-center py-4">
            <div class="flex-1 min-w-35 px-2">
                <x-ui.text class="text-xs uppercase tracking-wide opacity-70 font-semibold mb-1 block">Subtotal Total</x-ui.text>
                <x-ui.text class="text-xl md:text-2xl font-bold wrap-break-word">${{ number_format($totals['subtotal'], 2) }}</x-ui.text>
            </div>
            <div class="flex-1 min-w-35 px-2">
                <x-ui.text class="text-xs uppercase tracking-wide opacity-70 font-semibold mb-1 block">Total Descuentos</x-ui.text>
                <x-ui.text class="text-xl md:text-2xl font-bold text-red-300 wrap-break-word">-${{ number_format($totals['coupon_discount'], 2) }}</x-ui.text>
            </div>
            <div class="flex-1 min-w-35 px-2">
                <x-ui.text class="text-xs uppercase tracking-wide opacity-70 font-semibold mb-1 block">Pago Usuarios</x-ui.text>
                <x-ui.text class="text-xl md:text-2xl font-bold wrap-break-word">${{ number_format($totals['user_payment'], 2) }}</x-ui.text>
            </div>
            <div class="flex-1 min-w-35 px-2">
                <x-ui.text class="text-xs uppercase tracking-wide opacity-70 font-semibold mb-1 block">Total Comisiones</x-ui.text>
                <x-ui.text class="text-xl md:text-2xl font-bold text-teal-300 wrap-break-word">${{ number_format($totals['commission'], 2) }}</x-ui.text>
            </div>
            <div class="flex-1 min-w-35 px-2">
                <x-ui.text class="text-xs uppercase tracking-wide opacity-70 font-semibold mb-1 block">Gran total</x-ui.text>
                <x-ui.text class="text-xl md:text-2xl font-bold wrap-break-word">${{ number_format($totals['total'], 2) }}</x-ui.text>
            </div>
        </div>
    </x-ui.card>
</x-ui.card>

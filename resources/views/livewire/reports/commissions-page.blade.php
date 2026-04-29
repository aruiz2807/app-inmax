<div>
    <x-slot name="header">
        {{ __('app.commissions') }}
    </x-slot>

    <x-ui.card size="full" class="mb-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <x-ui.field>
                <x-ui.label>Año</x-ui.label>
                <x-ui.select wire:model.live="year">
                    @foreach($this->years as $y)
                        <x-ui.select.option value="{{ $y }}">{{ $y }}</x-ui.select.option>
                    @endforeach
                </x-ui.select>
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Mes</x-ui.label>
                <x-ui.select wire:model.live="month">
                    @foreach($this->months as $value => $label)
                        <x-ui.select.option value="{{ $value }}">{{ $label }}</x-ui.select.option>
                    @endforeach
                </x-ui.select>
            </x-ui.field>

            <x-ui.field class="md:col-span-2">
                <x-ui.label>Médico / Proveedor</x-ui.label>
                <x-ui.select
                    placeholder="Todos los médicos"
                    searchable
                    clearable
                    wire:model.live="doctor_id"
                >
                    @foreach($doctors as $doctor)
                        <x-ui.select.option value="{{ $doctor->id }}">
                            {{ $doctor->user->name }}
                        </x-ui.select.option>
                    @endforeach
                </x-ui.select>
            </x-ui.field>
        </div>
    </x-ui.card>
    
    
    @if($groupedAppointments->isEmpty())
        <x-ui.card>
            <div class="text-center py-8">
                <x-ui.text class="text-neutral-500">
                    No se encontraron comisiones para este periodo.
                </x-ui.text>
            </div>
        </x-ui.card>
    @else
        @foreach($groupedAppointments as $doctorName => $appointments)
            <div class="mb-8">
                <div class="flex items-center gap-2 mb-4">
                    <x-ui.icon name="user" variant="mini" class="text-teal-600" />
                    <x-ui.heading size="sm">
                        {{ $doctorName }} 
                        <span class="ml-2 text-xs font-normal text-neutral-500 bg-neutral-100 dark:bg-neutral-800 px-2 py-0.5 rounded-full">
                            {{ $appointments->count() }} {{ $appointments->count() === 1 ? 'cita' : 'citas' }}
                        </span>
                    </x-ui.heading>
                </div>

                <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900 shadow-sm">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-neutral-50 text-neutral-600 dark:bg-neutral-950 dark:text-neutral-400">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Fecha</th>
                                <th class="px-4 py-3 font-semibold">Paciente</th>
                                <th class="px-4 py-3 font-semibold text-right">Subtotal</th>
                                <th class="px-4 py-3 font-semibold text-right">Descuento</th>
                                <th class="px-4 py-3 font-semibold text-right">Pago Usuario</th>
                                <th class="px-4 py-3 font-semibold text-right">Comisión</th>
                                <th class="px-4 py-3 font-semibold text-right">Total</th>
                                <th class="px-4 py-3 font-semibold text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200 dark:divide-neutral-800">
                            @foreach($appointments as $appointment)
                                <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-950/50 transition-colors">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        {{ $appointment->date->format('d/m/Y') }}
                                    </td>
                                    <td class="px-4 py-3 font-medium">
                                        {{ $appointment->user->name }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        ${{ number_format($appointment->subtotal, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-red-500">
                                        -${{ number_format($appointment->coupon_discount, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        ${{ number_format($appointment->user_payment, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-teal-600">
                                        ${{ number_format($appointment->commission, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-bold">
                                        ${{ number_format($appointment->total, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <x-ui.button
                                            variant="ghost"
                                            size="sm"
                                            icon="eye"
                                            wire:click="showDetails({{ $appointment->id }})"
                                        />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-neutral-50/50 dark:bg-neutral-950/30 font-bold border-t border-neutral-200 dark:border-neutral-800">
                            <tr>
                                <td colspan="2" class="px-4 py-3 text-right">Subtotal Médico</td>
                                <td class="px-4 py-3 text-right">${{ number_format($appointments->sum('subtotal'), 2) }}</td>
                                <td class="px-4 py-3 text-right text-red-500">-${{ number_format($appointments->sum('coupon_discount'), 2) }}</td>
                                <td class="px-4 py-3 text-right">${{ number_format($appointments->sum('user_payment'), 2) }}</td>
                                <td class="px-4 py-3 text-right text-teal-600">${{ number_format($appointments->sum('commission'), 2) }}</td>
                                <td class="px-4 py-3 text-right">${{ number_format($appointments->sum('total'), 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        @endforeach

        <x-ui.card size="full" class="mt-12">
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
                    <x-ui.text class="text-xs uppercase tracking-wide opacity-70 font-semibold mb-1 block">Gran Total</x-ui.text>
                    <x-ui.text class="text-xl md:text-2xl font-bold wrap-break-word">${{ number_format($totals['total'], 2) }}</x-ui.text>
                </div>
            </div>
        </x-ui.card>
    @endif

    <x-ui.modal
        id="appointment-details"
        animation="fade"
        width="2xl"
        heading="Detalles de la Cita"
        x-on:close-modal.window="$data.close()"
        x-on:open-modal.window="$data.open()"
    >
        @if($selectedAppointment)
            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <x-ui.label class="opacity-50 text-xs uppercase tracking-wider">Paciente</x-ui.label>
                        <x-ui.text class="font-semibold text-lg">{{ $selectedAppointment->user?->name ?? 'N/A' }}</x-ui.text>
                    </div>
                    <div>
                        <x-ui.label class="opacity-50 text-xs uppercase tracking-wider">Médico / Proveedor</x-ui.label>
                        <x-ui.text class="font-semibold text-lg">{{ $selectedAppointment->doctor?->user?->name ?? 'N/A' }}</x-ui.text>
                    </div>
                    <div>
                        <x-ui.label class="opacity-50 text-xs uppercase tracking-wider">Fecha</x-ui.label>
                        <x-ui.text class="font-semibold">{{ $selectedAppointment->date?->format('d/m/Y') }}</x-ui.text>
                    </div>
                    <div>
                        <x-ui.label class="opacity-50 text-xs uppercase tracking-wider">Especialidad</x-ui.label>
                        <x-ui.text class="font-semibold">{{ $selectedAppointment->doctor?->specialty?->name ?? 'N/A' }}</x-ui.text>
                    </div>
                </div>

                <x-ui.separator class="my-8" />

                <x-ui.heading size="sm" class="mb-4">Servicios Realizados</x-ui.heading>
                <div class="space-y-3">
                    @foreach($selectedAppointment->services as $appService)
                        <div class="flex justify-between items-center p-4 bg-neutral-50 rounded-xl dark:bg-neutral-800 border border-neutral-100 dark:border-neutral-700">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-teal-100 dark:bg-teal-900/30 rounded-lg text-teal-600">
                                    <x-ui.icon name="check-circle" variant="mini" />
                                </div>
                                <x-ui.text class="font-medium">{{ $appService->service?->name ?? 'Servicio desconocido' }}</x-ui.text>
                            </div>
                            @if($appService->covered)
                            <x-ui.badge variant="outline" color="teal">{{ $appService->covered_text }}</x-ui.badge>
                            @else
                            <x-ui.badge variant="outline" color="amber">{{ $appService->covered_text }}</x-ui.badge>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="mt-10 flex justify-end">
                    <x-ui.button variant="outline" x-on:click="$data.close()">
                        Cerrar
                    </x-ui.button>
                </div>
            </div>
        @endif
    </x-ui.modal>
</div>

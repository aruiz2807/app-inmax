<div>
    <x-slot name="header">
        {{ __('app.sales') }}
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
                <x-ui.label>Vendedor</x-ui.label>
                <x-ui.select
                    placeholder="Todos los vendedores"
                    searchable
                    clearable
                    wire:model.live="sales_user_id"
                >
                    @foreach($salesUsers as $user)
                        <x-ui.select.option value="{{ $user->id }}">
                            {{ $user->name }}
                        </x-ui.select.option>
                    @endforeach
                </x-ui.select>
            </x-ui.field>
        </div>
    </x-ui.card>
    
    
    @if($groupedPolicies->isEmpty())
        <x-ui.card>
            <div class="text-center py-8">
                <x-ui.text class="text-neutral-500">
                    No se encontraron ventas para este periodo.
                </x-ui.text>
            </div>
        </x-ui.card>
    @else
        @foreach($groupedPolicies as $salesUserName => $policies)
            <div class="mb-8">
                <div class="flex items-center gap-2 mb-4">
                    <x-ui.icon name="user" variant="mini" class="text-teal-600" />
                    <x-ui.heading size="sm">
                        {{ $salesUserName }} 
                        <span class="ml-2 text-xs font-normal text-neutral-500 bg-neutral-100 dark:bg-neutral-800 px-2 py-0.5 rounded-full">
                            {{ $policies->count() }} {{ $policies->count() === 1 ? 'venta' : 'ventas' }}
                        </span>
                    </x-ui.heading>
                </div>

                <div class="overflow-x-auto rounded-xl border border-neutral-200 bg-white dark:border-neutral-800 dark:bg-neutral-900 shadow-sm">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-neutral-50 text-neutral-600 dark:bg-neutral-950 dark:text-neutral-400">
                            <tr>
                                <th class="px-4 py-3 font-semibold">Fecha</th>
                                <th class="px-4 py-3 font-semibold">Cliente</th>
                                <th class="px-4 py-3 font-semibold">Plan</th>
                                <th class="px-4 py-3 font-semibold text-right">Precio</th>
                                <th class="px-4 py-3 font-semibold text-right">Comisión</th>
                                <th class="px-4 py-3 font-semibold text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-neutral-200 dark:divide-neutral-800">
                            @foreach($policies as $policy)
                                <tr class="hover:bg-neutral-50 dark:hover:bg-neutral-950/50 transition-colors">
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        {{ $policy->start_date->format('d/m/Y') }}
                                    </td>
                                    <td class="px-4 py-3 font-medium">
                                        {{ $policy->user->name }}
                                    </td>
                                    <td class="px-4 py-3">
                                        {{ $policy->plan->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        ${{ number_format($policy->plan_price, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-teal-600">
                                        ${{ number_format($policy->calculated_commission, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <x-ui.button
                                            variant="ghost"
                                            size="sm"
                                            icon="eye"
                                            wire:click="showDetails({{ $policy->id }})"
                                        />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-neutral-50/50 dark:bg-neutral-950/30 font-bold border-t border-neutral-200 dark:border-neutral-800">
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-right">Subtotal Vendedor</td>
                                <td class="px-4 py-3 text-right">${{ number_format($policies->sum('plan_price'), 2) }}</td>
                                <td class="px-4 py-3 text-right text-teal-600">${{ number_format($policies->sum('calculated_commission'), 2) }}</td>
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
                    <x-ui.text class="text-xs uppercase tracking-wide opacity-70 font-semibold mb-1 block">Ventas Totales</x-ui.text>
                    <x-ui.text class="text-xl md:text-2xl font-bold wrap-break-word">${{ number_format($totals['price'], 2) }}</x-ui.text>
                </div>
                <div class="flex-1 min-w-35 px-2">
                    <x-ui.text class="text-xs uppercase tracking-wide opacity-70 font-semibold mb-1 block">Total Comisiones</x-ui.text>
                    <x-ui.text class="text-xl md:text-2xl font-bold text-teal-300 wrap-break-word">${{ number_format($totals['commission'], 2) }}</x-ui.text>
                </div>
            </div>
        </x-ui.card>
    @endif

    <x-ui.modal
        id="policy-details"
        animation="fade"
        width="2xl"
        heading="Detalles de la Membresía"
        x-on:close-modal.window="$data.close()"
        x-on:open-modal.window="$data.open()"
    >
        @if($selectedPolicy)
            <div class="p-8">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <x-ui.label class="opacity-50 text-xs uppercase tracking-wider">Cliente</x-ui.label>
                        <x-ui.text class="font-semibold text-lg">{{ $selectedPolicy->user?->name ?? 'N/A' }}</x-ui.text>
                    </div>
                    <div>
                        <x-ui.label class="opacity-50 text-xs uppercase tracking-wider">Vendedor</x-ui.label>
                        <x-ui.text class="font-semibold text-lg">{{ $selectedPolicy->sales_user?->name ?? 'N/A' }}</x-ui.text>
                    </div>
                    <div>
                        <x-ui.label class="opacity-50 text-xs uppercase tracking-wider">Fecha de Inicio</x-ui.label>
                        <x-ui.text class="font-semibold">{{ $selectedPolicy->start_date?->format('d/m/Y') }}</x-ui.text>
                    </div>
                    <div>
                        <x-ui.label class="opacity-50 text-xs uppercase tracking-wider">Plan</x-ui.label>
                        <x-ui.text class="font-semibold">{{ $selectedPolicy->plan?->name ?? 'N/A' }}</x-ui.text>
                    </div>
                </div>

                <x-ui.separator class="my-8" />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div>
                        <x-ui.label class="opacity-50 text-xs uppercase tracking-wider">Método de Pago</x-ui.label>
                        <x-ui.text class="font-semibold">{{ $selectedPolicy->payment_method ?? 'N/A' }}</x-ui.text>
                    </div>
                    <div>
                        <x-ui.label class="opacity-50 text-xs uppercase tracking-wider">Referencia de Pago</x-ui.label>
                        <x-ui.text class="font-semibold">{{ $selectedPolicy->payment_reference ?? 'N/A' }}</x-ui.text>
                    </div>
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

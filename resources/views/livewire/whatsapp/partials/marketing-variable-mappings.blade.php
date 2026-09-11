<div class="rounded-xl border border-slate-200 bg-white p-4">
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-slate-900">{{ $title }}</p>
            <p class="mt-1 text-xs text-slate-500">Elige de donde saldra cada variable de la plantilla.</p>
        </div>
        <span class="rounded-full bg-slate-100 px-2 py-1 text-xs text-slate-600">{{ count($mappings) }}</span>
    </div>

    <div class="mt-4 space-y-3">
        @forelse ($mappings as $index => $mapping)
            <div class="rounded-xl border border-slate-100 bg-slate-50 p-3" wire:key="{{ $property }}-{{ $index }}">
                <div class="mb-3">
                    <p class="text-sm font-medium text-slate-900">{{ $mapping['label'] ?? 'Variable '.($index + 1) }}</p>
                    <p class="text-xs text-slate-500">Variable {{ $index + 1 }}</p>
                </div>

                <div class="grid gap-3 md:grid-cols-2">
                    <div>
                        <x-ui.label>Origen</x-ui.label>
                        <select wire:model.live="{{ $property }}.{{ $index }}.source_type"
                            class="w-full rounded-box border border-black/10 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/15">
                            <option value="column">Columna Excel/CSV</option>
                            <option value="system">Dato del sistema</option>
                            <option value="static">Texto fijo</option>
                        </select>
                        <x-ui.error name="{{ $property }}.{{ $index }}.source_type" />
                    </div>

                    @if (($mapping['source_type'] ?? 'column') === 'system')
                        <div>
                            <x-ui.label>Campo del sistema</x-ui.label>
                            <select wire:model="{{ $property }}.{{ $index }}.system_key"
                                class="w-full rounded-box border border-black/10 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/15">
                                <option value="">Selecciona campo</option>
                                @foreach ($systemOptions as $key => $label)
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            <x-ui.error name="{{ $property }}.{{ $index }}.system_key" />
                        </div>
                    @elseif (($mapping['source_type'] ?? 'column') === 'static')
                        <div>
                            <x-ui.label>Texto fijo</x-ui.label>
                            <x-ui.input wire:model="{{ $property }}.{{ $index }}.static_value" placeholder="Valor fijo" />
                            <x-ui.error name="{{ $property }}.{{ $index }}.static_value" />
                        </div>
                    @else
                        <div>
                            <x-ui.label>Columna</x-ui.label>
                            <select wire:model="{{ $property }}.{{ $index }}.column_key"
                                class="w-full rounded-box border border-black/10 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-sm focus:outline-none focus:ring-2 focus:ring-neutral-900/15">
                                <option value="">Selecciona columna</option>
                                @foreach ($importColumns as $column)
                                    <option value="{{ $column['key'] }}">{{ $column['label'] }}</option>
                                @endforeach
                            </select>
                            <x-ui.error name="{{ $property }}.{{ $index }}.column_key" />
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 p-4 text-center text-sm text-slate-500">
                Esta seccion no requiere variables.
            </div>
        @endforelse
    </div>
</div>

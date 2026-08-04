@php
    $variables = data_get($this, $property, []);
@endphp

<div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
    <div class="flex items-center justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-slate-900">{{ $title }}</p>
            <p class="text-xs text-slate-500">El orden debe coincidir con Meta.</p>
        </div>

        <x-ui.button type="button" size="sm" icon="plus-circle" variant="outline" color="teal" wire:click="{{ $addMethod }}">
            Agregar
        </x-ui.button>
    </div>

    <div class="space-y-3 pt-4">
        @forelse ($variables as $index => $variable)
            <div wire:key="{{ $property }}-{{ $index }}" class="rounded-xl border border-slate-200 bg-white p-3">
                <div class="mb-3 flex items-center justify-between gap-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Variable {{ $index + 1 }}
                    </p>

                    <x-ui.button type="button" size="sm" icon="trash" variant="outline" color="red" wire:click="{{ $removeMethod }}({{ $index }})" />
                </div>

                <div class="grid gap-3">
                    <x-ui.field required>
                        <x-ui.label>Etiqueta</x-ui.label>
                        <x-ui.input wire:model="{{ $property }}.{{ $index }}.label" placeholder="Nombre del cliente" />
                        <x-ui.error name="{{ $property }}.{{ $index }}.label" />
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Ayuda</x-ui.label>
                        <x-ui.input wire:model="{{ $property }}.{{ $index }}.help_text" placeholder="Dato que debe capturar el operador" />
                        <x-ui.error name="{{ $property }}.{{ $index }}.help_text" />
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Ejemplo</x-ui.label>
                        <x-ui.input wire:model="{{ $property }}.{{ $index }}.example_value" placeholder="Juan Perez" />
                        <x-ui.error name="{{ $property }}.{{ $index }}.example_value" />
                    </x-ui.field>

                    <div class="grid gap-3 md:grid-cols-2">
                        <x-ui.field required>
                            <x-ui.label>Origen</x-ui.label>
                            <select
                                wire:model.live="{{ $property }}.{{ $index }}.source_type"
                                class="w-full rounded-box border border-black/10 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-sm transition-colors focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15"
                            >
                                <option value="custom">Texto libre</option>
                                <option value="system">Campo del sistema</option>
                            </select>
                            <x-ui.error name="{{ $property }}.{{ $index }}.source_type" />
                        </x-ui.field>

                        <x-ui.field>
                            <x-ui.label>Campo sistema</x-ui.label>
                            <select
                                wire:model="{{ $property }}.{{ $index }}.system_key"
                                @disabled(($variable['source_type'] ?? 'custom') !== 'system')
                                class="w-full rounded-box border border-black/10 bg-white px-3 py-2.5 text-sm text-neutral-900 shadow-sm transition-colors disabled:bg-slate-100 disabled:text-slate-400 focus:border-black/15 focus:outline-none focus:ring-2 focus:ring-neutral-900/15"
                            >
                                <option value="">Selecciona campo</option>
                                @foreach ($systemOptions as $optionValue => $optionLabel)
                                    <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
                                @endforeach
                            </select>
                            <x-ui.error name="{{ $property }}.{{ $index }}.system_key" />
                        </x-ui.field>
                    </div>

                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" wire:model="{{ $property }}.{{ $index }}.required" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                        Requerida
                    </label>
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-4 text-sm text-slate-500">
                Sin variables configuradas.
            </div>
        @endforelse
    </div>
</div>

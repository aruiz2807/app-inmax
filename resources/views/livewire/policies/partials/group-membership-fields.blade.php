@php($statePath = $statePath ?? 'form')
@php($plans = $plans ?? collect())
@php($salesAgents = $salesAgents ?? collect())
@php($promoterReadonly = $promoterReadonly ?? false)
@php($promoterName = $promoterName ?? '')
@php($planReadonly = $planReadonly ?? false)
@php($planName = $planName ?? '')

<x-ui.fieldset label="Información de la membresía" class="mt-2">
    <x-ui.field required>
        <x-ui.label>Plan</x-ui.label>
        @if($planReadonly)
            <x-ui.input :value="$planName" readonly copyable="false" />
        @else
            <x-ui.select
                placeholder="Buscar plan..."
                icon="wallet"
                searchable
                wire:model="{{ $statePath }}.plan"
            >
                @foreach($plans as $plan)
                    <x-ui.select.option value="{{ $plan->id }}">
                        {{ $plan->name }}
                    </x-ui.select.option>
                @endforeach
            </x-ui.select>
        @endif
        <x-ui.error :name="$statePath . '.plan'" />
    </x-ui.field>

    <x-ui.field required>
        <x-ui.label>Cantidad de miembros</x-ui.label>
        <x-ui.input wire:model="{{ $statePath }}.members" type="number" max="99" min="10" />
        <x-ui.error :name="$statePath . '.members'" />
    </x-ui.field>

    @if($promoterReadonly)
        <x-ui.field>
            <x-ui.label>Promotor</x-ui.label>
            <x-ui.input :value="$promoterName" readonly copyable="false" />
        </x-ui.field>
    @else
        <x-ui.field>
            <x-ui.label>Promotor</x-ui.label>
            <x-ui.select
                placeholder="Buscar promotor..."
                icon="wallet"
                searchable
                wire:model="{{ $statePath }}.sales_user"
            >
                @foreach($salesAgents as $agent)
                    <x-ui.select.option value="{{ $agent->id }}">
                        {{ $agent->name }}
                    </x-ui.select.option>
                @endforeach
            </x-ui.select>
            <x-ui.error :name="$statePath . '.sales_user'" />
        </x-ui.field>
    @endif
</x-ui.fieldset>

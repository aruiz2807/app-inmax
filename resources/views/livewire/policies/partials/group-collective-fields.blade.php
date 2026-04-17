@php($statePath = $statePath ?? 'form')
@php($readonly = $readonly ?? false)

<x-ui.fieldset label="Información del colectivo">
    <x-ui.field required>
        <x-ui.label>Nombre</x-ui.label>
        @if($readonly)
            <x-ui.input wire:model="{{ $statePath }}.company" placeholder="Inmax" readonly />
        @else
            <x-ui.input wire:model="{{ $statePath }}.company" placeholder="Inmax" />
        @endif
        <x-ui.error :name="$statePath . '.company'" />
    </x-ui.field>

    <x-ui.field required>
        <x-ui.label>Tipo</x-ui.label>
        @if($readonly)
            <x-ui.select wire:model="{{ $statePath }}.type" disabled>
                <x-ui.select.option value="PF">Persona fisica</x-ui.select.option>
                <x-ui.select.option value="PM">Persona moral</x-ui.select.option>
                <x-ui.select.option value="PFA">Persona fisica con actividad empresarial</x-ui.select.option>
            </x-ui.select>
        @else
            <x-ui.select wire:model="{{ $statePath }}.type">
                <x-ui.select.option value="PF">Persona fisica</x-ui.select.option>
                <x-ui.select.option value="PM">Persona moral</x-ui.select.option>
                <x-ui.select.option value="PFA">Persona fisica con actividad empresarial</x-ui.select.option>
            </x-ui.select>
        @endif
        <x-ui.error :name="$statePath . '.type'" />
    </x-ui.field>

    <x-ui.field required>
        <x-ui.label>Razon social</x-ui.label>
        @if($readonly)
            <x-ui.input wire:model="{{ $statePath }}.legal_name" placeholder="Inmax SA de CV" readonly />
        @else
            <x-ui.input wire:model="{{ $statePath }}.legal_name" placeholder="Inmax SA de CV" />
        @endif
        <x-ui.error :name="$statePath . '.legal_name'" />
    </x-ui.field>

    <x-ui.field required>
        <x-ui.label>RFC</x-ui.label>
        @if($readonly)
            <x-ui.input wire:model="{{ $statePath }}.rfc" placeholder="XAXX010101111" maxlength="13" readonly />
        @else
            <x-ui.input wire:model="{{ $statePath }}.rfc" placeholder="XAXX010101111" maxlength="13" />
        @endif
        <x-ui.error :name="$statePath . '.rfc'" />
    </x-ui.field>
</x-ui.fieldset>

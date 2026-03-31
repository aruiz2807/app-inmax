@php($statePath = $statePath ?? 'form')
@php($age = $age ?? null)
@php($phoneReadonly = $phoneReadonly ?? false)

<x-ui.fieldset label="Información del representante/miembro principal">
    <x-ui.field required>
        <x-ui.label>Nombre</x-ui.label>
        <x-ui.input wire:model="{{ $statePath }}.name" placeholder="Nombre Apellido" />
        <x-ui.error :name="$statePath . '.name'" />
    </x-ui.field>

    <x-ui.field required>
        <x-ui.label>Correo electrónico</x-ui.label>
        <x-ui.input wire:model="{{ $statePath }}.email" type="email" placeholder="nombre@correo.com" />
        <x-ui.error :name="$statePath . '.email'" />
    </x-ui.field>

    <x-ui.field required>
        <x-ui.label>Celular</x-ui.label>
        @if($phoneReadonly)
            <x-ui.input wire:model="{{ $statePath }}.phone" placeholder="3300000000" readonly />
        @else
            <x-ui.input wire:model="{{ $statePath }}.phone" placeholder="3300000000" />
        @endif
        <x-ui.error :name="$statePath . '.phone'" />
    </x-ui.field>

    <x-ui.field required>
        <x-ui.label>Fecha de nacimeinto</x-ui.label>
        <x-ui.input wire:model.blur="{{ $statePath }}.birth" type="date" placeholder="dd/mm/aaaa" leftIcon="calendar" />

        @if($age !== null)
            <x-ui.text class="opacity-75 pl-1 pt-1">Edad actual: {{ $age }} años</x-ui.text>
        @endif

        <x-ui.error :name="$statePath . '.birth'" />
    </x-ui.field>

    <x-ui.field class="text-left">
        <div class="flex justify-end mt-4">
            <x-ui.switch wire:model.live="{{ $statePath }}.foreigner" label="Es extranjero?" />
        </div>
    </x-ui.field>

    @if(data_get($this, $statePath . '.foreigner'))
        <x-ui.field>
            <x-ui.label>Pasaporte</x-ui.label>
            <x-ui.input wire:model="{{ $statePath }}.passport" />
            <x-ui.error :name="$statePath . '.passport'" />
        </x-ui.field>
    @else
        <x-ui.field>
            <x-ui.label>CURP</x-ui.label>
            <x-ui.input wire:model="{{ $statePath }}.curp" maxlength="18" />
            <x-ui.error :name="$statePath . '.curp'" />
        </x-ui.field>
    @endif

    <x-ui.field>
        <x-ui.label>Seguros adicionales</x-ui.label>
        <div class="flex justify-center">
            <x-ui.checkbox.group wire:model="{{ $statePath }}.insurance" variant="pills">
                <x-ui.checkbox label=" IMSS " value="imss" />
                <x-ui.checkbox label="ISSSTE" value="issste" />
                <x-ui.checkbox label=" SGMM " value="sgmm" />
            </x-ui.checkbox.group>
        </div>
    </x-ui.field>
</x-ui.fieldset>

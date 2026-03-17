@php($phoneReadonly = $phoneReadonly ?? false)

<x-ui.fieldset label="Informacion del cliente">
    <x-ui.field required>
        <x-ui.avatar size="xl" icon="user" color="teal" :src="$form->attachment ? $form->attachment->temporaryUrl() : $form->photo" circle class="mb-1 ml-4"/>
        <input type="file" wire:model="form.attachment" placeholder="Seleccione un archivo para adjuntar" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200"/>
        <x-ui.error name="form.attachment" />
    </x-ui.field>

    <x-ui.field required>
        <x-ui.label>Nombre completo</x-ui.label>
        <x-ui.input wire:model="form.name" name="name" placeholder="Angel Nuno" />
        <x-ui.error name="form.name" />
    </x-ui.field>

    <x-ui.field required>
        <x-ui.label>Correo electronico</x-ui.label>
        <x-ui.input wire:model="form.email" name="email" type="email" placeholder="angel.nuno@mail.com" />
        <x-ui.error name="form.email" />
    </x-ui.field>

    <x-ui.field required>
        <x-ui.label>Celular</x-ui.label>
        <x-ui.input wire:model="form.phone" name="phone" placeholder="3310203040" @readonly($phoneReadonly) />
        <x-ui.error name="form.phone" />
    </x-ui.field>

    <x-ui.field required>
        <x-ui.label>Fecha de nacimiento</x-ui.label>
        <x-ui.input wire:model.blur="form.birth" type="date" placeholder="dd/mm/aaaa" leftIcon="calendar" />

        @if($this->age !== null)
            <x-ui.text class="opacity-75 pl-1 pt-1">Edad actual: {{ $this->age }} anos</x-ui.text>
        @endif

        <x-ui.error name="form.birth" />
    </x-ui.field>

    <x-ui.field class="text-left">
        <div class="flex justify-end mt-4">
            <x-ui.switch wire:model.live="form.foreigner" label="Es extranjero?" />
        </div>
    </x-ui.field>

    @if($form->foreigner)
        <x-ui.field>
            <x-ui.label>Pasaporte</x-ui.label>
            <x-ui.input wire:model="form.passport" name="passport" />
            <x-ui.error name="form.passport" />
        </x-ui.field>
    @else
        <x-ui.field>
            <x-ui.label>CURP</x-ui.label>
            <x-ui.input wire:model="form.curp" name="curp" maxlength="18"/>
            <x-ui.error name="form.curp" />
        </x-ui.field>
    @endif

    <x-ui.field>
        <x-ui.label>Seguros adicionales</x-ui.label>
        <div class="flex justify-center">
            <x-ui.checkbox.group wire:model="form.insurance" variant="pills">
                <x-ui.checkbox label=" IMSS " value="imss" />
                <x-ui.checkbox label="ISSSTE" value="issste" />
                <x-ui.checkbox label=" SGMM " value="sgmm" />
            </x-ui.checkbox.group>
        </div>
    </x-ui.field>
</x-ui.fieldset>

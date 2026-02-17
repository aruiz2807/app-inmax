<div>
    <form wire:submit="save">
        <x-ui.fieldset label="Información del colecticvo">
            <x-ui.field required>
                <x-ui.label>Nombre</x-ui.label>
                <x-ui.input wire:model="form.company" name="company" placeholder="Inmax" />
                <x-ui.error name="form.company" />
            </x-ui.field>

            <x-ui.field required>
                <x-ui.label>Tipo</x-ui.label>
                <x-ui.select
                    icon="wallet"
                    wire:model="form.type">
                        <x-ui.select.option value="PF">Persona fisica</x-ui.select.option>
                        <x-ui.select.option value="PM">Persona moral</x-ui.select.option>
                        <x-ui.select.option value="PFA">Persona fisica con actividad empresarial</x-ui.select.option>
                </x-ui.select>
                <x-ui.error name="form.type" />
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>Razon social</x-ui.label>
                <x-ui.input wire:model="form.legal_name" name="legal_name" placeholder="Inmax SA de CV" />
                <x-ui.error name="form.legal_name" />
            </x-ui.field>

            <x-ui.field>
                <x-ui.label>RFC</x-ui.label>
                <x-ui.input wire:model="form.rfc" name="rfc" placeholder="XAXX010101111" />
                <x-ui.error name="form.rfc" />
            </x-ui.field>
        </x-ui.fieldset>

        <x-ui.fieldset label="Información del representante/miembro principal">
            <x-ui.field required>
                <x-ui.label>Nombre</x-ui.label>
                <x-ui.input wire:model="form.name" name="name" placeholder="Angel Nuño" />
                <x-ui.error name="form.company" />
            </x-ui.field>

            <x-ui.field required>
                <x-ui.label>Correo electrónico</x-ui.label>
                <x-ui.input wire:model="form.email" name="email" type="email" placeholder="angel.nuño@mail.com" />
                <x-ui.error name="form.email" />
            </x-ui.field>

            <x-ui.field required>
                <x-ui.label>Celular</x-ui.label>
                <x-ui.input wire:model="form.phone" name="phone" placeholder="3310203040" />
                <x-ui.error name="form.phone" />
            </x-ui.field>

            <x-ui.field required>
                <x-ui.label>Fecha de nacimeinto</x-ui.label>
                <x-ui.input wire:model.blur="form.birth" type="date" placeholder="dd/mm/aaaa" leftIcon="calendar" />

                @if($this->age !== null)
                <x-ui.text class="opacity-75 pl-1 pt-1">Edad actual: {{ $this->age }} años</x-ui.text>
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
        </x-ui.fieldset>

        <x-ui.fieldset label="Información de la poliza">
            <x-ui.field required>
                <x-ui.label>Cobertura</x-ui.label>
                <x-ui.select
                    placeholder="Buscar cobertura..."
                    icon="wallet"
                    searchable
                    wire:model="form.plan">
                        @foreach($plans as $plan)
                            <x-ui.select.option value="{{ $plan->id }}">
                                {{ $plan->name }}
                            </x-ui.select.option>
                        @endforeach
                </x-ui.select>
                <x-ui.error name="form.plan" />
            </x-ui.field>

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

        <div class="w-full flex justify-end gap-3 pt-4">
            <x-ui.button x-on:click="$data.close();" icon="x-mark" variant="outline">
                Cancel
            </x-ui.button>

            <x-ui.button type="submit" icon="check" variant="primary" color="teal">
                Guardar
            </x-ui.button>
        </div>
    </form>
</div>

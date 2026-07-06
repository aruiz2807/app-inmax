<div>
    <form wire:submit="save">
        @include('livewire.policies.partials.individual-customer-fields')

        <x-ui.fieldset label="Información de la membresía" class="mt-2">
            <x-ui.field required>
                <x-ui.label>Plan</x-ui.label>
                <x-ui.select
                    placeholder="Buscar plan..."
                    icon="wallet"
                    :class="$form->addingMember ? 'pointer-events-none opacity-50' : ''"
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
                <x-ui.label>Membresía principal</x-ui.label>
                <x-ui.select
                    placeholder="Buscar membresía..."
                    icon="wallet"
                    :class="$form->addingMember ? 'pointer-events-none opacity-50' : ''"
                    searchable
                    wire:model="form.parent_policy">
                        @foreach($policies as $policy)
                            <x-ui.select.option value="{{ $policy->id }}">
                                {{ $policy->number }} - {{ $policy->user->name }} - {{ $policy->user->company?->name }}
                            </x-ui.select.option>
                        @endforeach
                </x-ui.select>
                <x-ui.error name="form.parent_policy" />
            </x-ui.field>

            @if($form->sales_user)
            <x-ui.field>
                <x-ui.label>Promotor</x-ui.label>
                <x-ui.input :value="auth()->user()->name" readonly copyable="false" />
            </x-ui.field>
            @else
            <x-ui.field>
                <x-ui.label>Promotor</x-ui.label>
                <x-ui.select
                    placeholder="Buscar promotor..."
                    icon="wallet"
                    searchable
                    wire:model="form.sales_user">
                        @foreach($sales_agents as $agent)
                            <x-ui.select.option value="{{ $agent->id }}">
                                {{ $agent->name }}
                            </x-ui.select.option>
                        @endforeach
                </x-ui.select>
                <x-ui.error name="form.sales_user" />
            </x-ui.field>
            @endif
        </x-ui.fieldset>

        <x-ui.fieldset label="Información del responsable" class="mt-2">

            <x-ui.field class="text-left">
                <div class="flex justify-end mt-4">
                    <x-ui.switch wire:model.live="form.same_as_user" label="Mismo que el usuario?" />
                </div>
            </x-ui.field>

            <x-ui.field required>
                <x-ui.label>Nombre completo</x-ui.label>
                <x-ui.input wire:model="form.legal_name" name="legal_name" placeholder="Nombre Apellido" :readonly="$form->same_as_user" />
                <x-ui.error name="form.legal_name" />
            </x-ui.field>

            <x-ui.field required>
                <x-ui.label>Dirección</x-ui.label>
                <x-ui.textarea wire:model="form.legal_address" name="legal_address" />
                <x-ui.error name="form.legal_address" />
            </x-ui.field>

            @if (($this->age !== null && $this->age < 18) || !$form->same_as_user)
            <x-ui.field required>
                <x-ui.label>Parentesco</x-ui.label>
                <x-ui.select
                    placeholder="Buscar parentesco..."
                    icon="wallet"
                    searchable
                    wire:model="form.legal_relationship_id">
                        @foreach($relationships as $relationship)
                            <x-ui.select.option value="{{ $relationship->id }}">
                                {{ $relationship->name }}
                            </x-ui.select.option>
                        @endforeach
                </x-ui.select>
                <x-ui.error name="form.legal_relationship_id" />
            </x-ui.field>
            @endif

            <x-ui.field required>
                <x-ui.label>RFC</x-ui.label>
                <x-ui.input wire:model="form.cfdi_rfc" name="legal_name" placeholder="XAXX010101000" />
                <x-ui.error name="form.cfdi_rfc" />
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

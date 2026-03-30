<div>
    <x-slot name="header">
        {{ __('app.doctors') }}
    </x-slot>

    <div>
        <x-ui.card size="full">
            <x-ui.heading class="flex items-center justify-between mb-4" level="h3" size="sm">
                <span>Catalogo de proveedores</span>

                <x-ui.modal.trigger id="doctor-modal" wire:click="resetForm">
                    <x-ui.button color="teal" icon="plus-circle">
                        Agregar proveedor
                    </x-ui.button>
                </x-ui.modal.trigger>
            </x-ui.heading>

            <p>Administre los proveedores que pueden ser asignados para dar servicios</p>
        </x-ui.card>
    </div>

    <div class="pt-2">
        <x-ui.card size="full">
            <livewire:doctors.doctors-table />
        </x-ui.card>
    </div>

    <x-ui.modal
        id="doctor-modal"
        animation="fade"
        width="2xl"
        heading="{{$doctorId ? 'Editar proveedor' : 'Nuevo proveedor'}}"
        description="Ingrese la siguiente información para registrar un proveedor"
        x-on:close-doctor-modal.window="$data.close()"
        x-on:open-doctor-modal.window="$data.open()"
    >
        <form wire:submit="save">
            <x-ui.fieldset label="Información del proveedor">
                <x-ui.field required>
                    <x-ui.label>Tipo</x-ui.label>
                    <x-ui.select
                        placeholder="Buscar tipo..."
                        icon="wallet"
                        searchable
                        wire:model.live="form.type">
                            @foreach($types as $type)
                                <x-ui.select.option :value="$type->value">
                                    {{ $type->label() }}
                                </x-ui.select.option>
                            @endforeach
                    </x-ui.select>
                    <x-ui.error name="form.type" />
                </x-ui.field>

                <x-ui.field required>
                    <x-ui.label>Nombre completo</x-ui.label>
                    <x-ui.input wire:model="form.name" name="name" placeholder="Angel Nuño" />
                    <x-ui.error name="form.name" />
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
                    <x-ui.label>Especialidad</x-ui.label>
                    <x-ui.select
                        placeholder="Buscar especialidad..."
                        icon="wallet"
                        searchable
                        wire:model="form.specialty">
                            @foreach($specialties as $specialty)
                                <x-ui.select.option value="{{ $specialty->id }}">
                                    {{ $specialty->name }}
                                </x-ui.select.option>
                            @endforeach
                    </x-ui.select>
                    <x-ui.error name="form.specialty" />
                </x-ui.field>

                @if($this->form->type === 'Doctor')
                <x-ui.field :required="$this->doctorRequired">
                    <x-ui.label>Cedula profesional</x-ui.label>
                    <x-ui.input wire:model="form.license" name="license" />
                    <x-ui.error name="form.license" />
                </x-ui.field>

                <x-ui.field :required="$this->doctorRequired">
                    <x-ui.label>Universidad</x-ui.label>
                    <x-ui.input wire:model="form.university" name="university" />
                    <x-ui.error name="form.university" />
                </x-ui.field>
                @endif

                <x-ui.field required>
                    <x-ui.label>Dirección</x-ui.label>
                    <x-ui.textarea wire:model="form.address" name="address" />
                    <x-ui.error name="form.address" />
                </x-ui.field>

                <x-ui.field required>
                    <x-ui.label>Google Maps URL</x-ui.label>
                    <x-ui.textarea wire:model="form.maps_url" name="maps_url" />
                    <x-ui.error name="form.maps_url" />
                </x-ui.field>

                <x-ui.field required>
                    <x-ui.label>Descuento</x-ui.label>
                    <x-ui.input wire:model="form.discount" name="discount" prefixIcon="percent-badge" type="number" min="0" max="100"/>
                    <x-ui.error name="form.discount" />
                </x-ui.field>

                <x-ui.field required>
                    <x-ui.label>Comisión</x-ui.label>
                    <x-ui.input wire:model="form.commission" name="commission" prefixIcon="percent-badge" type="number" min="0" max="100" />
                    <x-ui.error name="form.commission" />
                </x-ui.field>

            </x-ui.fieldset>

            <div class="w-full flex justify-end gap-3 pt-4">
                <x-ui.button x-on:click="$data.close();" wire:click="resetForm" icon="x-mark" variant="outline">
                    Cancel
                </x-ui.button>

                <x-ui.button type="submit" icon="check" variant="primary" color="teal">
                    Guardar
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>
</div>

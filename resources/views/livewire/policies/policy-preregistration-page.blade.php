<div>
    <x-authentication-card>
        <x-slot name="logo">
            <x-ui.brand
                href="/"
                logo="/img/logo.png"
                name="INMAX"
                alt="INMAX"
                logoClass="rounded-full size-12"
            />
        </x-slot>

        <x-validation-errors class="mb-4" />

        @if ($registrationCompleted)
            <div class="space-y-4">
                <div class="rounded-xl border border-teal-200 bg-teal-50 px-4 py-4 text-sm text-teal-950">
                    <p class="text-base font-semibold">
                        Todo listo, {{ $registeredMemberName }}.
                    </p>
                    <p class="mt-3">
                        Tus datos han sido registrados correctamente en el sistema de INMAX.
                    </p>
                    <p class="mt-3">
                        Tu membresía esta a un paso de quedar activa. Para comenzar a disfrutar de tus beneficios y recibir tu kit de bienvenida, te esperamos en nuestra sucursal para realizar tu pago.
                    </p>
                    <p class="mt-3">
                        Horarios de atención (Caja): Lunes a Viernes: 9:00 AM - 6:00 PM. Sabados: 9:00 AM - 2:00 PM.
                    </p>
                    <p class="mt-3">
                        Al llegar, solo menciona que ya completaste tu registro en linea. Te esperamos para activar tu membresía INMAX.
                    </p>
                    <p class="mt-3">
                        Ubicación: Torre Medica, Av. Plan de San Luis #1831, Col. San Bernardo, C.P. 44260.
                    </p>
                </div>

                <div class="flex flex-wrap justify-end gap-3">
                    <x-ui.button
                        href="{{ $officeMapsUrl }}"
                        target="_blank"
                        rel="noopener noreferrer"
                        color="teal"
                        icon="map"
                    >
                        Ver en Google Maps
                    </x-ui.button>

                    <x-ui.button href="/" color="teal" variant="outline" icon="arrow-left-end-on-rectangle">
                        Ir al inicio
                    </x-ui.button>
                </div>
            </div>
        @elseif ($this->canRegister())
            <div class="mb-4 text-sm text-neutral-600 dark:text-neutral-300">
                Completa tus datos para terminar el registro de tu membresía. Al finalizar te mostraremos los siguientes pasos para activarla.
            </div>
        @endif

        @if ($preregistration)
            <div class="mb-4 rounded-lg border border-neutral-200 p-3 text-sm dark:border-neutral-700">
                <p><span class="font-semibold">Teléfono:</span> {{ $preregistration->phone }}</p>
                <p><span class="font-semibold">Tipo:</span> {{ $preregistration->type_label }}</p>
                <p><span class="font-semibold">Plan:</span> {{ $preregistration->plan?->name ?: 'Se definira durante el registro' }}</p>
                <p><span class="font-semibold">Membresía principal:</span> {{ $preregistration->parentPolicy?->number ?: 'Sin membresía principal' }}</p>
                @if($preregistration->isGroupOwner())
                    <p><span class="font-semibold">Colectivo:</span> {{ $preregistration->company_name }}</p>
                    <p><span class="font-semibold">Razon social:</span> {{ $preregistration->company_legal_name }}</p>
                    <p><span class="font-semibold">RFC:</span> {{ $preregistration->company_rfc }}</p>
                @endif
                <p><span class="font-semibold">Promotor:</span> {{ $preregistration->salesUser?->name }}</p>
            </div>
        @endif

        @if (! $this->canRegister() && $tokenMessage)
            <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                {{ $tokenMessage }}
            </div>

            <div class="mt-4 flex justify-end">
                <x-ui.button href="/" color="teal" icon="arrow-left-end-on-rectangle">
                    Ir al inicio
                </x-ui.button>
            </div>
        @endif

        @if ($this->canRegister() && ! $registrationCompleted)
            <form wire:submit="save">
                @if($preregistration?->isGroupOwner())
                    @include('livewire.policies.partials.group-collective-fields', [
                        'statePath' => 'groupForm',
                        'readonly' => true,
                    ])

                    @include('livewire.policies.partials.group-representative-fields', [
                        'statePath' => 'groupForm',
                        'age' => $this->groupAge,
                        'phoneReadonly' => true,
                    ])

                    @include('livewire.policies.partials.group-membership-fields', [
                        'statePath' => 'groupForm',
                        'plans' => collect(),
                        'salesAgents' => collect(),
                        'planReadonly' => true,
                        'planName' => $preregistration?->plan?->name,
                        'membersReadonly' => true,
                        'promoterReadonly' => true,
                        'promoterName' => $preregistration?->salesUser?->name,
                    ])
                @else
                    @include('livewire.policies.partials.individual-customer-fields', ['phoneReadonly' => true])

                    <x-ui.fieldset label="Informacion de la membresía" class="mt-2">
                        <x-ui.field>
                            <x-ui.label>Plan</x-ui.label>
                            <x-ui.input :value="$preregistration?->plan?->name" readonly copyable="false" />
                        </x-ui.field>

                        <x-ui.field>
                            <x-ui.label>Membresía principal</x-ui.label>
                            <x-ui.input :value="$preregistration?->parentPolicy?->number ?: 'Sin membresía principal'" readonly copyable="false" />
                        </x-ui.field>

                        <x-ui.field>
                            <x-ui.label>Promotor</x-ui.label>
                            <x-ui.input :value="$preregistration?->salesUser?->name" readonly copyable="false" />
                        </x-ui.field>
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
                            <x-ui.label>Parentesco del usuario con el responsable</x-ui.label>
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
                @endif

                <div class="mt-4 flex items-center justify-end">
                    <x-ui.button type="submit" color="teal" icon="check">
                        Completar registro
                    </x-ui.button>
                </div>
            </form>
        @endif
    </x-authentication-card>
</div>

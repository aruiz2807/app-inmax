<div>
    <x-authentication-card>
        <x-slot name="logo">
            <x-ui.brand
                href="/"
                logo="/img/logo.png"
                name="Inmax-Sure"
                alt="Inmax"
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
                        Tu membresia esta a un paso de quedar activa. Para comenzar a disfrutar de tus beneficios y recibir tu kit de bienvenida, te esperamos en nuestra sucursal para realizar tu pago.
                    </p>
                    <p class="mt-3">
                        Horarios de atencion (Caja): Lunes a Viernes: 9:00 AM - 6:00 PM. Sabados: 9:00 AM - 2:00 PM.
                    </p>
                    <p class="mt-3">
                        Al llegar, solo menciona que ya completaste tu registro en linea. Te esperamos para activar tu Membresia INMAX.
                    </p>
                    <p class="mt-3">
                        Ubicacion: Torre Medica, Av. Plan de San Luis #1831, Col. San Bernardo, C.P. 44260.
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
                Completa tus datos para terminar el registro de tu poliza. Al finalizar te mostraremos los siguientes pasos para activar tu membresia.
            </div>
        @endif

        @if ($preregistration)
            <div class="mb-4 rounded-lg border border-neutral-200 p-3 text-sm dark:border-neutral-700">
                <p><span class="font-semibold">Telefono:</span> {{ $preregistration->phone }}</p>
                <p><span class="font-semibold">Cobertura:</span> {{ $preregistration->plan?->name }}</p>
                <p><span class="font-semibold">Poliza principal:</span> {{ $preregistration->parentPolicy?->number ?: 'Sin poliza principal' }}</p>
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
                @include('livewire.policies.partials.individual-customer-fields', ['phoneReadonly' => true])

                <x-ui.fieldset label="Informacion de la poliza" class="mt-2">
                    <x-ui.field>
                        <x-ui.label>Cobertura</x-ui.label>
                        <x-ui.input :value="$preregistration?->plan?->name" readonly copyable="false" />
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Poliza principal</x-ui.label>
                        <x-ui.input :value="$preregistration?->parentPolicy?->number ?: 'Sin poliza principal'" readonly copyable="false" />
                    </x-ui.field>

                    <x-ui.field>
                        <x-ui.label>Promotor</x-ui.label>
                        <x-ui.input :value="$preregistration?->salesUser?->name" readonly copyable="false" />
                    </x-ui.field>
                </x-ui.fieldset>

                <div class="mt-4 flex items-center justify-end">
                    <x-ui.button type="submit" color="teal" icon="check">
                        Completar registro
                    </x-ui.button>
                </div>
            </form>
        @endif
    </x-authentication-card>
</div>

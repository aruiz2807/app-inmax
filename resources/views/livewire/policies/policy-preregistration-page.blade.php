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

        @if ($this->canRegister())
            <div class="mb-4 text-sm text-neutral-600 dark:text-neutral-300">
                Completa tus datos para terminar el registro de tu poliza. Al finalizar te pediremos definir tu PIN de acceso.
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

        @if ($this->canRegister())
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

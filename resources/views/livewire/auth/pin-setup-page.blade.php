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

        @if ($this->canSetPin())
            <div class="mb-4 text-sm text-neutral-600 dark:text-neutral-300">
                Confirma tu acceso y define tu PIN de 4 digitos para iniciar sesion en Inmax-Sure.
            </div>
        @endif

        @if ($user)
            <div class="mb-4 rounded-lg border border-neutral-200 dark:border-neutral-700 p-3 text-sm">
                <p><span class="font-semibold">Nombre:</span> {{ $user?->name }}</p>
                <p><span class="font-semibold">Correo:</span> {{ $user?->email }}</p>
                <p><span class="font-semibold">Telefono:</span> {{ $user?->phone }}</p>
            </div>
        @endif

        @if (! $this->canSetPin() && $tokenMessage)
            <div class="mb-4 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                {{ $tokenMessage }}
            </div>

            <div class="flex justify-end mt-4">
                <x-ui.button href="{{ route('login') }}" color="teal" icon="arrow-left-end-on-rectangle">
                    Ir a login
                </x-ui.button>
            </div>
        @endif

        @if ($this->canSetPin())
            <form wire:submit="save">
                <div>
                    <x-label for="pin" value="PIN (4 digitos)" />
                    <x-input
                        id="pin"
                        class="block mt-1 w-full"
                        type="password"
                        inputmode="numeric"
                        maxlength="4"
                        wire:model="pin"
                        required
                        autofocus
                    />
                    <x-ui.error name="pin" />
                </div>

                <div class="mt-4">
                    <x-label for="pin_confirmation" value="Confirmar PIN" />
                    <x-input
                        id="pin_confirmation"
                        class="block mt-1 w-full"
                        type="password"
                        inputmode="numeric"
                        maxlength="4"
                        wire:model="pin_confirmation"
                        required
                    />
                </div>

                <div class="mt-6 rounded-lg border border-neutral-200 dark:border-neutral-700 p-4">
                    <p class="text-sm font-semibold">Consentimiento legal</p>

                    @if ($legalReady)
                        <div class="mt-3 space-y-2">
                            <x-checkbox id="accept_terms" wire:model="acceptTerms">
                                Acepto {{ $termsTitle }} (version {{ $termsVersion }})
                            </x-checkbox>
                            <x-ui.error name="acceptTerms" />

                            <x-checkbox id="accept_privacy" wire:model="acceptPrivacy">
                                Acepto {{ $privacyTitle }} (version {{ $privacyVersion }})
                            </x-checkbox>
                            <x-ui.error name="acceptPrivacy" />

                            <x-checkbox id="accept_sensitive" wire:model="acceptSensitiveData">
                                Consiento el tratamiento de datos personales sensibles para la prestacion del servicio.
                            </x-checkbox>
                            <x-ui.error name="acceptSensitiveData" />
                        </div>

                        <details class="mt-4 rounded-lg border border-neutral-200 dark:border-neutral-700 p-3">
                            <summary class="cursor-pointer text-sm font-semibold">Ver {{ $termsTitle }} ({{ $termsVersion }})</summary>
                            <div class="mt-2 max-h-36 overflow-auto text-sm whitespace-pre-line text-neutral-700 dark:text-neutral-300">
                                {{ $termsContent }}
                            </div>
                        </details>

                        <details class="mt-3 rounded-lg border border-neutral-200 dark:border-neutral-700 p-3">
                            <summary class="cursor-pointer text-sm font-semibold">Ver {{ $privacyTitle }} ({{ $privacyVersion }})</summary>
                            <div class="mt-2 max-h-36 overflow-auto text-sm whitespace-pre-line text-neutral-700 dark:text-neutral-300">
                                {{ $privacyContent }}
                            </div>
                        </details>
                    @else
                        <div class="mt-3 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                            {{ $legalMessage }}
                        </div>
                    @endif
                </div>

                @if ($legalReady)
                    <div class="flex items-center justify-end mt-4">
                        <x-ui.button type="submit" color="teal" icon="check">
                            Confirmar PIN
                        </x-ui.button>
                    </div>
                @endif
            </form>
        @endif
    </x-authentication-card>
</div>

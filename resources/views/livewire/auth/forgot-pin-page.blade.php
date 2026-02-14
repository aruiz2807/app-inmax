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

        <div class="mb-4 text-sm text-neutral-600 dark:text-neutral-300">
            Ingresa tu telefono para validar tu cuenta y generar un enlace temporal para restablecer tu PIN.
        </div>

        <form wire:submit="sendResetLink">
            <div>
                <x-label for="phone" value="Telefono" />
                <x-input
                    id="phone"
                    class="block mt-1 w-full"
                    type="text"
                    wire:model="phone"
                    required
                    inputmode="numeric"
                    maxlength="10"
                    autofocus
                    autocomplete="tel"
                    placeholder="3310203040"
                />
                <x-ui.error name="phone" />
            </div>

            <div class="flex items-center justify-between mt-4">
                <a class="ui-link text-sm" href="{{ route('login') }}">
                    Volver a login
                </a>

                <x-ui.button type="submit" color="teal" icon="paper-airplane">
                    Generar enlace
                </x-ui.button>
            </div>
        </form>
    </x-authentication-card>
</div>

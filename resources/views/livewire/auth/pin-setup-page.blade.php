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
            Confirma tu acceso y define tu PIN de 6 digitos para iniciar sesion en Inmax-Sure.
        </div>

        <div class="mb-4 rounded-lg border border-neutral-200 dark:border-neutral-700 p-3 text-sm">
            <p><span class="font-semibold">Nombre:</span> {{ $user?->name }}</p>
            <p><span class="font-semibold">Correo:</span> {{ $user?->email }}</p>
            <p><span class="font-semibold">Telefono:</span> {{ $user?->phone }}</p>
        </div>

        <form wire:submit="save">
            <div>
                <x-label for="pin" value="PIN (6 digitos)" />
                <x-input
                    id="pin"
                    class="block mt-1 w-full"
                    type="password"
                    inputmode="numeric"
                    maxlength="6"
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
                    maxlength="6"
                    wire:model="pin_confirmation"
                    required
                />
            </div>

            <div class="flex items-center justify-end mt-4">
                <x-ui.button type="submit" color="teal" icon="check">
                    Confirmar PIN
                </x-ui.button>
            </div>
        </form>
    </x-authentication-card>
</div>

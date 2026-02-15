<x-guest-layout>
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

        @session('status')
            <div class="mb-4 font-medium text-sm text-green-600">
                {{ $value }}
            </div>
        @endsession

        <form method="POST" action="{{ route('admin.login.store') }}">
            @csrf

            <div>
                <x-label for="email" value="Correo administrador" />
                <x-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            </div>

            <div class="mt-4">
                <x-label for="password" value="Contrasena" />
                <x-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" />
            </div>

            <div class="block mt-4">
                <x-checkbox id="remember_me" name="remember">
                    {{ __('Remember me') }}
                </x-checkbox>
            </div>

            <div class="flex items-center justify-between mt-4">
                <a class="ui-link text-sm" href="{{ route('login') }}">
                    Volver a login por PIN
                </a>

                <x-ui.button type="submit" color="teal" icon="arrow-left-end-on-rectangle">
                    Ingresar como Admin
                </x-ui.button>
            </div>
        </form>
    </x-authentication-card>
</x-guest-layout>

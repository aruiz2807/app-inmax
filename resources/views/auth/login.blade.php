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

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <div>
                <x-label for="phone" value="Telefono" />
                <x-input id="phone" class="block mt-1 w-full" type="text" name="phone" :value="old('phone')" required autofocus inputmode="numeric" autocomplete="tel" />
            </div>

            <div class="mt-4" x-data="{ pin: '' }">
                <x-label for="pin" value="PIN" />
                <input type="hidden" name="password" x-model="pin" required />
                <x-ui.otp class="mt-1" x-model="pin" length="4" />
            </div>

            <div class="block mt-4">
                <x-checkbox id="remember_me" name="remember">
                    {{ __('Remember me') }}
                </x-checkbox>
            </div>

            <div class="flex items-center justify-end mt-4">
                <x-ui.button type="submit" color="teal" icon="arrow-left-end-on-rectangle">
                    {{ __('Log in') }}
                </x-ui.button>
            </div>
        </form>
    </x-authentication-card>
</x-guest-layout>

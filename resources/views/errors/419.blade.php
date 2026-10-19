
<x-app-layout>
    <div class="flex min-h-[60vh] flex-col items-center justify-center px-6 py-12 text-center">
        <h1 class="text-6xl font-bold">419</h1>
        <h2 class="mt-4 text-2xl font-semibold">{{ __('Sesión expirada') }}</h2>

        <x-ui.button href="/" color="orange" class="mt-6">
            {{ __('Volver al inicio') }}
        </x-ui.button>
    </div>
</x-app-layout>

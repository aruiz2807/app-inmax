<x-ui.card size="full">
    <x-application-logo class="block h-12 w-auto" />

    <x-ui.text class="mt-8 text-2xl font-semibold text-neutral-900">
        Hola {{ auth()->user()->name }}!
    </x-ui.text>

    <x-ui.text class="mt-2 text-lg">
        Bienvenido a la consola de administración de Inmax-Sure
    </x-ui.text>

    </div>
</x-ui.card>

